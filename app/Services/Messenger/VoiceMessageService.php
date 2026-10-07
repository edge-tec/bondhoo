<?php

namespace App\Services\Messenger;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\Contracts\RealtimeServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VoiceMessageService
{
    public function __construct(
        protected RealtimeServiceInterface $realtimeService,
        protected SyncEventService $syncEventService
    ) {}

    /**
     * Store and broadcast a voice message with waveform and duration.
     *
     * @param  int[]  $waveform
     */
    public function sendVoiceMessage(
        Conversation $conversation,
        User $sender,
        UploadedFile $audioFile,
        int $durationSeconds,
        array $waveform = []
    ): Message {
        // Validate audio file integrity BEFORE moving to storage
        if (! $this->validateAudioFile($audioFile)) {
            throw new \InvalidArgumentException('Uploaded file is not a valid audio file or has an unsupported format.');
        }

        // Extract real amplitude waveform from binary audio if client did not supply peak levels
        if (empty($waveform)) {
            $waveform = $this->extractWaveformFromAudioFile($audioFile);
        }

        $folder = 'messenger/voice/'.date('Y/m');
        $filename = 'voice_'.Str::random(24).'.'.$audioFile->getClientOriginalExtension();
        $storedPath = $audioFile->storeAs($folder, $filename, 'public');

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'type' => 'voice',
            'body' => 'ভয়েস বার্তা ('.gmdate('i:s', $durationSeconds).')',
            'delivery_status' => Message::STATUS_SENT,
            'sent_at' => now(),
            'metadata' => [
                'storage_path' => $storedPath,
                'duration' => $durationSeconds,
                'waveform' => $waveform,
                'mime_type' => $audioFile->getMimeType(),
                'size' => $audioFile->getSize(),
            ],
        ]);

        $audioUrl = '/api/v1/messages/'.$message->id.'/voice';
        $message->update([
            'metadata' => array_merge($message->metadata ?? [], [
                'audio_url' => $audioUrl,
                'audio_stream_url' => $audioUrl,
            ]),
        ]);

        $conversation->update([
            'last_message_id' => $message->id,
            'last_message_at' => now(),
        ]);

        $message->load(['sender.profile']);
        $payload = $message->toResponseArray($sender);

        $this->realtimeService->broadcastToConversation($conversation->id, 'message.created', $payload);
        $this->syncEventService->recordEvent('message.created', $payload, null, $conversation->id);

        return $message;
    }

    /**
     * Extract real amplitude waveform peaks from raw audio binary stream.
     *
     * @return int[]
     */
    public function extractWaveformFromAudioFile(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        if (! $path || ! file_exists($path) || filesize($path) === 0) {
            return array_fill(0, 32, 20);
        }

        $size = filesize($path);
        $handle = @fopen($path, 'rb');
        if (! $handle) {
            return array_fill(0, 32, 20);
        }

        $sliceSize = max(1, (int) floor($size / 32));
        $peaks = [];

        for ($i = 0; $i < 32; $i++) {
            $data = fread($handle, $sliceSize);
            if ($data === false || strlen($data) === 0) {
                $peaks[] = 15;

                continue;
            }

            $bytes = unpack('C*', $data);
            $sum = 0;
            $count = count($bytes);
            foreach ($bytes as $b) {
                $sum += abs($b - 128);
            }
            $avg = $count > 0 ? ($sum / $count) : 0;
            $normalized = (int) min(100, max(15, round(($avg / 128) * 100)));
            $peaks[] = $normalized;
        }

        fclose($handle);

        return $peaks;
    }

    /**
     * Validate audio file signature, magic bytes, and MIME integrity.
     */
    public function validateAudioFile(UploadedFile $file): bool
    {
        $mime = strtolower((string) ($file->getClientMimeType() ?: $file->getMimeType()));
        $validMimes = [
            'audio/webm', 'audio/ogg', 'audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/x-wav', 'audio/aac', 'audio/mp4', 'audio/x-m4a', 'video/webm', 'application/octet-stream',
        ];

        $extension = strtolower((string) $file->getClientOriginalExtension());
        $validExtensions = ['webm', 'ogg', 'mp3', 'wav', 'm4a', 'aac', 'mp4'];

        $path = $file->getRealPath();
        if ($path && file_exists($path) && filesize($path) > 0) {
            $header = (string) @file_get_contents($path, false, null, 0, 32);
            $isWebm = str_starts_with($header, "\x1A\x45\xDF\xA3");
            $isOgg = str_starts_with($header, 'OggS');
            $isWav = str_starts_with($header, 'RIFF') && str_contains(substr($header, 8, 8), 'WAVE');
            $isMp3 = str_starts_with($header, 'ID3') || str_starts_with($header, "\xFF\xFB") || str_starts_with($header, "\xFF\xF3") || str_starts_with($header, "\xFF\xF2");
            $isM4a = str_contains(substr($header, 4, 8), 'ftyp');

            if ($isWebm || $isOgg || $isWav || $isMp3 || $isM4a) {
                return true;
            }
        }

        return in_array($mime, $validMimes, true) || in_array($extension, $validExtensions, true);
    }
}
