package com.connectly.android.core.designsystem.component

import android.content.Context
import android.graphics.Bitmap
import android.graphics.BitmapFactory
import android.graphics.ImageDecoder
import android.graphics.Matrix
import android.net.Uri
import android.os.Build
import androidx.compose.foundation.Canvas
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.gestures.detectTransformGestures
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Check
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.filled.RotateRight
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.clipToBounds
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.geometry.Rect
import androidx.compose.ui.geometry.Size
import androidx.compose.ui.graphics.ClipOp
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.Path
import androidx.compose.ui.graphics.asImageBitmap
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.graphics.drawscope.clipPath
import androidx.compose.ui.graphics.graphicsLayer
import androidx.compose.ui.input.pointer.pointerInput
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.layout.onSizeChanged
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.IntSize
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import androidx.compose.ui.window.DialogProperties
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import java.io.File
import java.io.FileOutputStream
import kotlin.math.max
import kotlin.math.min
import kotlin.math.roundToInt

enum class CropAspectRatio(val label: String, val ratio: Float?) {
    SQUARE("1:1", 1f),
    PORTRAIT("4:5", 4f / 5f),
    COVER("16:9", 16f / 9f),
    ORIGINAL("Original", null)
}

/**
 * Full-screen Material 3 Compose image cropper supporting pinch-to-zoom, pan, 90° rotation,
 * circular avatar guides, and aspect-ratio presets (1:1, 4:5, 16:9, Original).
 */
@Composable
fun BondhooImageCropperDialog(
    sourceUri: Uri? = null,
    sourceFile: File? = null,
    title: String = "Crop Photo",
    initialAspectRatio: CropAspectRatio = CropAspectRatio.SQUARE,
    isCircular: Boolean = false,
    allowAspectRatioSelection: Boolean = false,
    onDismiss: () -> Unit,
    onCropComplete: (File) -> Unit
) {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()

    var rawBitmap by remember(sourceUri, sourceFile) { mutableStateOf<Bitmap?>(null) }
    var isLoading by remember { mutableStateOf(true) }
    var isSaving by remember { mutableStateOf(false) }
    var loadError by remember { mutableStateOf<String?>(null) }

    var selectedAspect by remember { mutableStateOf(initialAspectRatio) }
    var rotationDegrees by remember { mutableIntStateOf(0) }
    var scale by remember { mutableFloatStateOf(1f) }
    var offset by remember { mutableStateOf(Offset.Zero) }
    var viewportSize by remember { mutableStateOf(IntSize.Zero) }

    val displayBitmap by remember(rawBitmap, rotationDegrees) {
        derivedStateOf {
            val src = rawBitmap ?: return@derivedStateOf null
            if (rotationDegrees % 360 == 0) {
                src
            } else {
                val matrix = Matrix().apply { postRotate(rotationDegrees.toFloat()) }
                Bitmap.createBitmap(src, 0, 0, src.width, src.height, matrix, true)
            }
        }
    }

    LaunchedEffect(sourceUri, sourceFile) {
        isLoading = true
        loadError = null
        val loaded = withContext(Dispatchers.IO) {
            decodeSourceBitmap(context, sourceUri, sourceFile, maxDimension = 2048)
        }
        if (loaded == null) {
            loadError = "Could not load selected image."
        } else {
            rawBitmap = loaded
        }
        isLoading = false
    }

    Dialog(
        onDismissRequest = { if (!isSaving) onDismiss() },
        properties = DialogProperties(
            usePlatformDefaultWidth = false,
            decorFitsSystemWindows = false
        )
    ) {
        Surface(
            modifier = Modifier.fillMaxSize(),
            color = Color(0xFF0B0E14)
        ) {
            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .systemBarsPadding()
            ) {
                // Top Bar
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 12.dp, vertical = 10.dp),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    IconButton(onClick = { if (!isSaving) onDismiss() }) {
                        Icon(Icons.Default.Close, contentDescription = "Cancel", tint = Color.White)
                    }
                    Text(
                        text = title,
                        color = Color.White,
                        fontSize = 18.sp,
                        fontWeight = FontWeight.Bold
                    )
                    Button(
                        onClick = {
                            val bmp = displayBitmap ?: return@Button
                            if (viewportSize.width <= 0 || viewportSize.height <= 0) return@Button
                            isSaving = true
                            scope.launch {
                                val croppedFile = withContext(Dispatchers.IO) {
                                    renderCroppedBitmapToFile(
                                        context = context,
                                        bitmap = bmp,
                                        viewportSize = viewportSize,
                                        aspectRatio = selectedAspect.ratio
                                            ?: (bmp.width.toFloat() / bmp.height.toFloat()),
                                        scale = scale,
                                        offset = offset
                                    )
                                }
                                isSaving = false
                                if (croppedFile != null) {
                                    onCropComplete(croppedFile)
                                } else {
                                    loadError = "Failed to save cropped image."
                                }
                            }
                        },
                        enabled = displayBitmap != null && !isSaving,
                        colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
                        contentPadding = PaddingValues(horizontal = 16.dp, vertical = 8.dp),
                        shape = RoundedCornerShape(20.dp)
                    ) {
                        if (isSaving) {
                            CircularProgressIndicator(
                                modifier = Modifier.size(16.dp),
                                strokeWidth = 2.dp,
                                color = Color.White
                            )
                        } else {
                            Icon(Icons.Default.Check, contentDescription = null, modifier = Modifier.size(18.dp))
                            Spacer(modifier = Modifier.width(6.dp))
                            Text("Done", fontWeight = FontWeight.SemiBold)
                        }
                    }
                }

                // Interactive Crop Canvas
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .weight(1f)
                        .clipToBounds()
                        .onSizeChanged { viewportSize = it }
                        .pointerInput(displayBitmap, selectedAspect) {
                            detectTransformGestures { _, pan, zoom, _ ->
                                val newScale = (scale * zoom).coerceIn(1f, 4.5f)
                                val maxOffsetX = (viewportSize.width * (newScale - 0.5f)).coerceAtLeast(120f)
                                val maxOffsetY = (viewportSize.height * (newScale - 0.5f)).coerceAtLeast(120f)
                                scale = newScale
                                offset = Offset(
                                    x = (offset.x + pan.x).coerceIn(-maxOffsetX, maxOffsetX),
                                    y = (offset.y + pan.y).coerceIn(-maxOffsetY, maxOffsetY)
                                )
                            }
                        },
                    contentAlignment = Alignment.Center
                ) {
                    when {
                        isLoading -> {
                            CircularProgressIndicator(color = ConnectlyBlue)
                        }
                        loadError != null -> {
                            Text(loadError!!, color = Color(0xFFF87171), fontSize = 14.sp)
                        }
                        displayBitmap != null -> {
                            val bmp = displayBitmap!!
                            val imageBitmap = remember(bmp) { bmp.asImageBitmap() }
                            val effectiveAspect = selectedAspect.ratio ?: (bmp.width.toFloat() / bmp.height.toFloat())

                            Image(
                                bitmap = imageBitmap,
                                contentDescription = "Crop preview",
                                contentScale = ContentScale.Fit,
                                modifier = Modifier
                                    .fillMaxSize()
                                    .graphicsLayer {
                                        scaleX = scale
                                        scaleY = scale
                                        translationX = offset.x
                                        translationY = offset.y
                                    }
                            )

                            // Scrim & Crop Frame Overlay
                            Canvas(modifier = Modifier.fillMaxSize()) {
                                val cropRect = computeCropRect(size.width, size.height, effectiveAspect)
                                val cutoutPath = Path().apply {
                                    if (isCircular && selectedAspect == CropAspectRatio.SQUARE) {
                                        addOval(cropRect)
                                    } else {
                                        addRect(cropRect)
                                    }
                                }
                                clipPath(cutoutPath, clipOp = ClipOp.Difference) {
                                    drawRect(Color.Black.copy(alpha = 0.62f))
                                }

                                // Frame outline
                                if (isCircular && selectedAspect == CropAspectRatio.SQUARE) {
                                    drawOval(
                                        color = Color.White,
                                        topLeft = cropRect.topLeft,
                                        size = cropRect.size,
                                        style = Stroke(width = 2.dp.toPx())
                                    )
                                } else {
                                    drawRect(
                                        color = Color.White,
                                        topLeft = cropRect.topLeft,
                                        size = cropRect.size,
                                        style = Stroke(width = 2.dp.toPx())
                                    )
                                    // Rule-of-thirds grid lines
                                    val thirdW = cropRect.width / 3f
                                    val thirdH = cropRect.height / 3f
                                    for (i in 1..2) {
                                        drawLine(
                                            color = Color.White.copy(alpha = 0.35f),
                                            start = Offset(cropRect.left + thirdW * i, cropRect.top),
                                            end = Offset(cropRect.left + thirdW * i, cropRect.bottom),
                                            strokeWidth = 1.dp.toPx()
                                        )
                                        drawLine(
                                            color = Color.White.copy(alpha = 0.35f),
                                            start = Offset(cropRect.left, cropRect.top + thirdH * i),
                                            end = Offset(cropRect.right, cropRect.top + thirdH * i),
                                            strokeWidth = 1.dp.toPx()
                                        )
                                    }
                                }
                            }
                        }
                    }
                }

                // Bottom Controls (Aspect Ratios + Rotate + Reset)
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .background(Color(0xFF141821))
                        .padding(horizontal = 16.dp, vertical = 14.dp),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    if (allowAspectRatioSelection) {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceEvenly
                        ) {
                            CropAspectRatio.entries.forEach { preset ->
                                val isSelected = selectedAspect == preset
                                Surface(
                                    shape = RoundedCornerShape(16.dp),
                                    color = if (isSelected) ConnectlyBlue else Color(0xFF232A38),
                                    modifier = Modifier.clickable {
                                        selectedAspect = preset
                                        scale = 1f
                                        offset = Offset.Zero
                                    }
                                ) {
                                    Text(
                                        text = preset.label,
                                        color = Color.White,
                                        fontSize = 13.sp,
                                        fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Normal,
                                        modifier = Modifier.padding(horizontal = 14.dp, vertical = 7.dp)
                                    )
                                }
                            }
                        }
                    }

                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceEvenly,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        TextButton(
                            onClick = {
                                rotationDegrees = (rotationDegrees + 90) % 360
                                scale = 1f
                                offset = Offset.Zero
                            }
                        ) {
                            Icon(Icons.Default.RotateRight, contentDescription = "Rotate", tint = Color.White)
                            Spacer(modifier = Modifier.width(6.dp))
                            Text("Rotate 90°", color = Color.White)
                        }

                        TextButton(
                            onClick = {
                                rotationDegrees = 0
                                scale = 1f
                                offset = Offset.Zero
                            }
                        ) {
                            Icon(Icons.Default.Refresh, contentDescription = "Reset", tint = Color.White)
                            Spacer(modifier = Modifier.width(6.dp))
                            Text("Reset", color = Color.White)
                        }
                    }
                }
            }
        }
    }
}

private fun computeCropRect(viewWidth: Float, viewHeight: Float, aspectRatio: Float): Rect {
    val padding = min(viewWidth, viewHeight) * 0.08f
    val maxW = (viewWidth - padding * 2f).coerceAtLeast(1f)
    val maxH = (viewHeight - padding * 2f).coerceAtLeast(1f)

    val cropW: Float
    val cropH: Float
    if (maxW / maxH > aspectRatio) {
        cropH = maxH
        cropW = cropH * aspectRatio
    } else {
        cropW = maxW
        cropH = cropW / aspectRatio
    }

    val left = (viewWidth - cropW) / 2f
    val top = (viewHeight - cropH) / 2f
    return Rect(Offset(left, top), Size(cropW, cropH))
}

private fun decodeSourceBitmap(
    context: Context,
    uri: Uri?,
    file: File?,
    maxDimension: Int
): Bitmap? {
    return try {
        val raw = when {
            uri != null && Build.VERSION.SDK_INT >= Build.VERSION_CODES.P -> {
                val source = ImageDecoder.createSource(context.contentResolver, uri)
                ImageDecoder.decodeBitmap(source) { decoder, info, _ ->
                    decoder.allocator = ImageDecoder.ALLOCATOR_SOFTWARE
                    val maxEdge = max(info.size.width, info.size.height)
                    if (maxEdge > maxDimension) {
                        val ratio = maxDimension.toFloat() / maxEdge.toFloat()
                        decoder.setTargetSize(
                            (info.size.width * ratio).roundToInt().coerceAtLeast(1),
                            (info.size.height * ratio).roundToInt().coerceAtLeast(1)
                        )
                    }
                }
            }
            uri != null -> {
                context.contentResolver.openInputStream(uri)?.use { stream ->
                    BitmapFactory.decodeStream(stream)
                }
            }
            file != null && file.exists() -> {
                val opts = BitmapFactory.Options().apply { inJustDecodeBounds = true }
                BitmapFactory.decodeFile(file.absolutePath, opts)
                val maxEdge = max(opts.outWidth, opts.outHeight)
                var sample = 1
                while (maxEdge / sample > maxDimension) sample *= 2
                BitmapFactory.decodeFile(
                    file.absolutePath,
                    BitmapFactory.Options().apply { inSampleSize = sample }
                )
            }
            else -> null
        } ?: return null

        val maxEdge = max(raw.width, raw.height)
        if (maxEdge > maxDimension) {
            val ratio = maxDimension.toFloat() / maxEdge.toFloat()
            Bitmap.createScaledBitmap(
                raw,
                (raw.width * ratio).roundToInt().coerceAtLeast(1),
                (raw.height * ratio).roundToInt().coerceAtLeast(1),
                true
            )
        } else {
            raw
        }
    } catch (_: Exception) {
        null
    }
}

private fun renderCroppedBitmapToFile(
    context: Context,
    bitmap: Bitmap,
    viewportSize: IntSize,
    aspectRatio: Float,
    scale: Float,
    offset: Offset
): File? {
    return try {
        val vw = viewportSize.width.toFloat()
        val vh = viewportSize.height.toFloat()
        val cropRect = computeCropRect(vw, vh, aspectRatio)

        // Fit-scale of bitmap inside the viewport before user's pinch/pan
        val fitScale = min(vw / bitmap.width.toFloat(), vh / bitmap.height.toFloat())
        val totalScale = fitScale * scale

        val displayedW = bitmap.width * totalScale
        val displayedH = bitmap.height * totalScale
        val imgLeft = (vw - displayedW) / 2f + offset.x
        val imgTop = (vh - displayedH) / 2f + offset.y

        // Map cropRect back into source bitmap pixel coordinates
        val srcLeft = ((cropRect.left - imgLeft) / totalScale).roundToInt()
            .coerceIn(0, (bitmap.width - 1).coerceAtLeast(0))
        val srcTop = ((cropRect.top - imgTop) / totalScale).roundToInt()
            .coerceIn(0, (bitmap.height - 1).coerceAtLeast(0))
        val srcRight = ((cropRect.right - imgLeft) / totalScale).roundToInt()
            .coerceIn(srcLeft + 1, bitmap.width)
        val srcBottom = ((cropRect.bottom - imgTop) / totalScale).roundToInt()
            .coerceIn(srcTop + 1, bitmap.height)

        val cropWidth = (srcRight - srcLeft).coerceAtLeast(1)
        val cropHeight = (srcBottom - srcTop).coerceAtLeast(1)

        val cropped = Bitmap.createBitmap(bitmap, srcLeft, srcTop, cropWidth, cropHeight)
        val maxOutput = 1440
        val finalBitmap = if (max(cropped.width, cropped.height) > maxOutput) {
            val ratio = maxOutput.toFloat() / max(cropped.width, cropped.height).toFloat()
            Bitmap.createScaledBitmap(
                cropped,
                (cropped.width * ratio).roundToInt().coerceAtLeast(1),
                (cropped.height * ratio).roundToInt().coerceAtLeast(1),
                true
            )
        } else {
            cropped
        }

        val outFile = File(context.cacheDir, "bondhoo_cropped_${System.currentTimeMillis()}.jpg")
        FileOutputStream(outFile).use { fos ->
            finalBitmap.compress(Bitmap.CompressFormat.JPEG, 90, fos)
        }
        outFile
    } catch (_: Exception) {
        null
    }
}
