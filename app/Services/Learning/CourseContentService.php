<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\LearningCourse;
use App\Models\LearningCourseLesson;
use App\Models\LearningCourseSection;
use App\Models\LearningLessonAttachment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CourseContentService
{
    /**
     * Add a section to a course.
     */
    public function addSection(LearningCourse $course, array $data): LearningCourseSection
    {
        $maxSort = (int) $course->sections()->max('sort_order');

        $section = LearningCourseSection::create([
            'course_id' => $course->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? ($maxSort + 1),
            'is_published' => $data['is_published'] ?? true,
        ]);

        $course->increment('total_sections');

        return $section;
    }

    /**
     * Update section.
     */
    public function updateSection(LearningCourseSection $section, array $data): LearningCourseSection
    {
        $section->update($data);

        return $section;
    }

    /**
     * Delete section and its lessons.
     */
    public function deleteSection(LearningCourseSection $section): void
    {
        DB::transaction(function () use ($section) {
            $course = $section->course;
            $lessonsCount = $section->lessons()->count();

            $section->lessons()->delete();
            $section->delete();

            $course->decrement('total_sections');
            if ($lessonsCount > 0) {
                $course->decrement('total_lessons', $lessonsCount);
            }
        });
    }

    /**
     * Add a lesson to a section.
     */
    public function addLesson(LearningCourseSection $section, array $data): LearningCourseLesson
    {
        return DB::transaction(function () use ($section, $data) {
            $course = $section->course;
            $maxSort = (int) $section->lessons()->max('sort_order');
            $slug = Str::slug($data['title']).'-'.Str::random(5);

            $lesson = LearningCourseLesson::create([
                'section_id' => $section->id,
                'course_id' => $course->id,
                'title' => $data['title'],
                'slug' => $slug,
                'type' => $data['type'] ?? LearningCourseLesson::TYPE_VIDEO,
                'content' => $data['content'] ?? null,
                'video_url' => $data['video_url'] ?? null,
                'video_duration_seconds' => (int) ($data['video_duration_seconds'] ?? 0),
                'sort_order' => $data['sort_order'] ?? ($maxSort + 1),
                'is_preview' => (bool) ($data['is_preview'] ?? false),
                'is_mandatory' => (bool) ($data['is_mandatory'] ?? true),
                'is_published' => (bool) ($data['is_published'] ?? true),
            ]);

            $course->increment('total_lessons');

            if ($lesson->video_duration_seconds > 0) {
                $additionalMinutes = (int) ceil($lesson->video_duration_seconds / 60);
                $course->increment('duration_minutes', $additionalMinutes);
            }

            return $lesson;
        });
    }

    /**
     * Update lesson.
     */
    public function updateLesson(LearningCourseLesson $lesson, array $data): LearningCourseLesson
    {
        $lesson->update($data);

        return $lesson;
    }

    /**
     * Delete lesson.
     */
    public function deleteLesson(LearningCourseLesson $lesson): void
    {
        DB::transaction(function () use ($lesson) {
            $course = $lesson->course;
            $durationSeconds = $lesson->video_duration_seconds;

            $lesson->delete();
            $course->decrement('total_lessons');

            if ($durationSeconds > 0) {
                $minutes = (int) ceil($durationSeconds / 60);
                $course->decrement('duration_minutes', min($minutes, $course->duration_minutes));
            }
        });
    }

    /**
     * Add an attachment to a lesson.
     */
    public function addAttachment(LearningCourseLesson $lesson, array $data): LearningLessonAttachment
    {
        $maxSort = (int) $lesson->attachments()->max('sort_order');

        return LearningLessonAttachment::create([
            'lesson_id' => $lesson->id,
            'title' => $data['title'],
            'file_url' => $data['file_url'],
            'file_type' => $data['file_type'] ?? 'pdf',
            'file_size' => (int) ($data['file_size'] ?? 0),
            'sort_order' => $data['sort_order'] ?? ($maxSort + 1),
        ]);
    }
}
