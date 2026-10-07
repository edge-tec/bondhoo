<?php

namespace App\Http\Requests\Profile;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

class UploadCoverRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240'], // Max 10MB
            'caption' => ['nullable', 'string', 'max:500'],
            'cover_position_y' => ['nullable', 'integer', 'between:0,100'],
            'crop_x' => ['nullable', 'integer', 'min:0'],
            'crop_y' => ['nullable', 'integer', 'min:0'],
            'crop_width' => ['nullable', 'integer', 'min:100'],
            'crop_height' => ['nullable', 'integer', 'min:100'],
        ];
    }

    /**
     * Configure the validator instance with deep content inspection.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var UploadedFile|null $file */
            $file = $this->file('file');

            if (! $file || ! $file->isValid()) {
                return;
            }

            $realPath = $file->getRealPath();
            if (! $realPath || ! file_exists($realPath)) {
                $validator->errors()->add('file', 'ফাইলটি সিস্টেমে লোড করা যায়নি।');

                return;
            }

            // 1. Verify real MIME type using PHP fileinfo (never trust client MIME)
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $actualMime = finfo_file($finfo, $realPath);
            finfo_close($finfo);

            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
            if (! in_array($actualMime, $allowedMimes, true)) {
                $validator->errors()->add('file', 'শুধুমাত্র JPEG, PNG অথবা WEBP ফরম্যাটের ছবি গ্রহণযোগ্য।');

                return;
            }

            // 2. Reject SVG or XML files to prevent SVG XSS
            if ($actualMime === 'image/svg+xml' || str_contains($actualMime, 'xml')) {
                $validator->errors()->add('file', 'নিরাপত্তা কারণে SVG ফরম্যাটের ছবি গ্রহণযোগ্য নয়।');

                return;
            }

            // 3. Verify real image structure and dimensions via getimagesize
            $imageInfo = @getimagesize($realPath);
            if ($imageInfo === false) {
                $validator->errors()->add('file', 'ফাইলটি একটি বৈধ ছবি নয় অথবা করাপ্টেড।');

                return;
            }

            $width = $imageInfo[0];
            $height = $imageInfo[1];

            if ($width < 400 || $height < 150) {
                $validator->errors()->add('file', 'কভার ছবির মাপ সর্বনিম্ন ৪০০x১৫০ পিক্সেল হতে হবে।');

                return;
            }

            if ($width > 6000 || $height > 4000) {
                $validator->errors()->add('file', 'কভার ছবির মাপ সর্বোচ্চ ৬০০০x৪০০০ পিক্সেল হতে পারে।');

                return;
            }

            // 4. Content Scan: check for embedded PHP or script payload
            $sample = @file_get_contents($realPath, false, null, 0, 8192);
            if ($sample !== false) {
                $lowerSample = strtolower($sample);
                if (
                    str_contains($lowerSample, '<?php') ||
                    str_contains($lowerSample, '<?=') ||
                    str_contains($lowerSample, '<script') ||
                    str_contains($lowerSample, '<svg')
                ) {
                    $validator->errors()->add('file', 'ফাইলটিতে নিরাপত্তা লঙ্ঘনকারী বা ক্ষতিকর কোড শনাক্ত হয়েছে।');

                    return;
                }
            }

            // 5. Extension blacklist check
            $originalExt = strtolower($file->getClientOriginalExtension());
            $forbiddenExts = ['php', 'phtml', 'php3', 'php4', 'php5', 'phps', 'svg', 'sh', 'exe', 'bat', 'bin', 'js', 'html', 'htm'];
            if (in_array($originalExt, $forbiddenExts, true)) {
                $validator->errors()->add('file', 'অনিরাপদ ফাইল এক্সটেনশন গ্রহণযোগ্য নয়।');
            }
        });
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'অনুগ্রহ করে একটি কভার ছবি নির্বাচন করুন।',
            'file.file' => 'নির্বাচিত আইটেমটি একটি ফাইল হতে হবে।',
            'file.max' => 'কভার ছবির সাইজ সর্বোচ্চ ১০MB হতে পারে।',
        ];
    }
}
