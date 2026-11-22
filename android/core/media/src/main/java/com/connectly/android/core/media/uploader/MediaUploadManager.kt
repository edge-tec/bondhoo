package com.connectly.android.core.media.uploader

import android.graphics.Bitmap
import android.graphics.BitmapFactory
import com.connectly.android.core.common.network.ConnectlyDispatchers
import com.connectly.android.core.common.network.Dispatcher
import com.connectly.android.core.media.model.UploadStatus
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.core.network.api.InitUploadRequest
import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flow
import kotlinx.coroutines.flow.flowOn
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.toRequestBody
import java.io.File
import java.io.FileInputStream
import java.io.FileOutputStream
import java.security.MessageDigest
import java.util.concurrent.ConcurrentHashMap
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class MediaUploadManager @Inject constructor(
    private val api: ConnectlyApi,
    @Dispatcher(ConnectlyDispatchers.IO) private val ioDispatcher: CoroutineDispatcher
) {
    private val activeUploadJobs = ConcurrentHashMap<String, Boolean>()

    /**
     * Upload a media file (Image or Video) using enterprise chunked & resumable upload.
     */
    fun uploadMedia(
        file: File,
        collection: String = "general",
        mimeType: String = if (file.name.endsWith(".mp4", ignoreCase = true)) "video/mp4" else "image/jpeg"
    ): Flow<UploadStatus> = flow {
        emit(UploadStatus.Idle)

        if (!file.exists() || file.length() == 0L) {
            emit(UploadStatus.Error("ফাইলটি পাওয়া যায়নি বা ফাইলের আকার শূন্য।", canRetry = false))
            return@flow
        }

        // 0. Smart client-side image compression for large camera photos (> 450 KB -> ~250-350 KB crisp HD)
        val compressedTempFile = compressImageIfNeeded(file, mimeType)
        val uploadFile = compressedTempFile ?: file
        val effectiveMimeType = if (compressedTempFile != null) "image/jpeg" else mimeType

        // 1. Calculate file checksum to detect corruption or duplicate uploads
        val fileChecksum = calculateSha256(uploadFile)

        // 2. Initialize upload session
        val initRequest = InitUploadRequest(
            filename = uploadFile.name,
            file_size = uploadFile.length(),
            mime_type = effectiveMimeType,
            collection = collection,
            checksum = fileChecksum
        )

        val initResponse = try {
            api.initChunkedUpload(initRequest)
        } catch (e: Exception) {
            compressedTempFile?.delete()
            emit(UploadStatus.Error("আপলোড সেশন শুরু করা যায়নি: ${e.message}"))
            return@flow
        }

        if (!initResponse.isSuccessful || initResponse.body() == null) {
            compressedTempFile?.delete()
            emit(UploadStatus.Error("সার্ভার থেকে আপলোড সেশন প্রত্যাখ্যান করা হয়েছে।"))
            return@flow
        }

        val session = initResponse.body()!!.data
        val sessionId = session.session_id
        val chunkSize = session.chunk_size
        val totalChunks = session.total_chunks
        val totalBytes = uploadFile.length()

        activeUploadJobs[sessionId] = true

        try {
            // Check if resuming is needed
            var uploadedChunks = emptySet<Int>()
            try {
                val resumeRes = api.resumeUpload(sessionId)
                if (resumeRes.isSuccessful && resumeRes.body() != null) {
                    uploadedChunks = resumeRes.body()!!.data.uploaded_chunks.toSet()
                }
            } catch (_: Exception) {
                // Fresh session, continue
            }

            // 3. Upload each chunk
            FileInputStream(uploadFile).use { inputStream ->
                val buffer = ByteArray(chunkSize)
                var chunkNumber = 1
                var bytesUploadedCumulative = 0L

                while (chunkNumber <= totalChunks) {
                    // Check if cancelled by user
                    if (activeUploadJobs[sessionId] != true) {
                        emit(UploadStatus.Cancelled)
                        try { api.cancelUpload(sessionId) } catch (_: Exception) {}
                        return@flow
                    }

                    val bytesRead = inputStream.read(buffer)
                    if (bytesRead == -1) break

                    if (chunkNumber in uploadedChunks) {
                        bytesUploadedCumulative += bytesRead
                        chunkNumber++
                        continue
                    }

                    val chunkBytes = if (bytesRead == chunkSize) buffer else buffer.copyOf(bytesRead)
                    val chunkRequestBody = chunkBytes.toRequestBody("application/octet-stream".toMediaTypeOrNull())
                    val chunkPart = MultipartBody.Part.createFormData("chunk", "${file.name}.part$chunkNumber", chunkRequestBody)

                    val sessionIdBody = sessionId.toRequestBody("text/plain".toMediaTypeOrNull())
                    val chunkNumberBody = chunkNumber.toString().toRequestBody("text/plain".toMediaTypeOrNull())

                    // Retry failed chunks up to 3 times with exponential backoff
                    var uploadSuccess = false
                    var attempts = 0
                    while (!uploadSuccess && attempts < 3) {
                        attempts++
                        try {
                            val chunkRes = api.uploadChunk(
                                chunk = chunkPart,
                                sessionId = sessionIdBody,
                                chunkNumber = chunkNumberBody
                            )
                            if (chunkRes.isSuccessful) {
                                uploadSuccess = true
                            } else {
                                delay(attempts * 1000L)
                            }
                        } catch (e: Exception) {
                            if (attempts >= 3) {
                                emit(UploadStatus.Error("চাঙ্ক $chunkNumber আপলোড ব্যর্থ হয়েছে (${e.message})। পুনরায় চেষ্টা করুন।"))
                                return@flow
                            }
                            delay(attempts * 1000L)
                        }
                    }

                    bytesUploadedCumulative += bytesRead
                    val progressPercent = (bytesUploadedCumulative.toFloat() / totalBytes.toFloat()).coerceIn(0f, 1f)

                    emit(
                        UploadStatus.Progress(
                            sessionId = sessionId,
                            currentChunk = chunkNumber,
                            totalChunks = totalChunks,
                            progressPercent = progressPercent,
                            bytesUploaded = bytesUploadedCumulative,
                            totalBytes = totalBytes
                        )
                    )

                    chunkNumber++
                }
            }

            // 4. Complete session assembly
            val completeRes = api.completeChunkedUpload(sessionId)
            if (completeRes.isSuccessful && completeRes.body() != null) {
                val mediaResponse = completeRes.body()!!.data.media
                emit(UploadStatus.Success(sessionId = sessionId, media = mediaResponse))
            } else {
                emit(UploadStatus.Error("মিডিয়া অ্যাসেম্বলি ব্যর্থ হয়েছে।"))
            }

        } finally {
            activeUploadJobs.remove(sessionId)
            compressedTempFile?.delete()
        }
    }.flowOn(ioDispatcher)

    /**
     * Compresses oversized camera photos (> 450 KB) to max 1920px JPEG (84% quality)
     * so uploads finish rapidly even on slow mobile networks.
     */
    private fun compressImageIfNeeded(source: File, mimeType: String): File? {
        if (!mimeType.startsWith("image/", ignoreCase = true) || mimeType.contains("gif", ignoreCase = true)) {
            return null
        }
        if (source.length() <= 450_000L) {
            return null
        }

        return try {
            val boundsOptions = BitmapFactory.Options().apply { inJustDecodeBounds = true }
            BitmapFactory.decodeFile(source.absolutePath, boundsOptions)
            val rawW = boundsOptions.outWidth
            val rawH = boundsOptions.outHeight
            if (rawW <= 0 || rawH <= 0) return null

            val maxDim = 1920
            var sampleSize = 1
            while ((rawW / sampleSize) > maxDim * 2 || (rawH / sampleSize) > maxDim * 2) {
                sampleSize *= 2
            }

            val decodeOptions = BitmapFactory.Options().apply { inSampleSize = sampleSize }
            val decoded = BitmapFactory.decodeFile(source.absolutePath, decodeOptions) ?: return null

            val scale = minOf(maxDim.toFloat() / decoded.width, maxDim.toFloat() / decoded.height, 1f)
            val finalBitmap = if (scale < 1f) {
                Bitmap.createScaledBitmap(
                    decoded,
                    (decoded.width * scale).toInt().coerceAtLeast(1),
                    (decoded.height * scale).toInt().coerceAtLeast(1),
                    true
                ).also { if (it != decoded) decoded.recycle() }
            } else {
                decoded
            }

            val outFile = File(source.parentFile ?: File(System.getProperty("java.io.tmpdir")), "opt_${System.currentTimeMillis()}_${source.nameWithoutExtension}.jpg")
            FileOutputStream(outFile).use { fos ->
                finalBitmap.compress(Bitmap.CompressFormat.JPEG, 84, fos)
            }
            finalBitmap.recycle()

            if (outFile.exists() && outFile.length() in 1 until source.length()) {
                outFile
            } else {
                outFile.delete()
                null
            }
        } catch (_: Exception) {
            null
        }
    }

    /**
     * Cancel an active upload session.
     */
    fun cancelUpload(sessionId: String) {
        activeUploadJobs[sessionId] = false
    }

    private fun calculateSha256(file: File): String {
        val digest = MessageDigest.getInstance("SHA-256")
        FileInputStream(file).use { fis ->
            val byteArray = ByteArray(8192)
            var bytesCount: Int
            while (fis.read(byteArray).also { bytesCount = it } != -1) {
                digest.update(byteArray, 0, bytesCount)
            }
        }
        val bytes = digest.digest()
        val sb = StringBuilder()
        for (b in bytes) {
            sb.append(String.format("%02x", b))
        }
        return sb.toString()
    }
}

