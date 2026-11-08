<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\LearningCategory;
use App\Models\LearningCourse;
use App\Models\LearningCourseSkill;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CourseService
{
    /**
     * Search and filter courses for the public marketplace.
     */
    public function getMarketplaceCourses(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = LearningCourse::with(['instructor', 'category', 'skills'])
            ->where('status', LearningCourse::STATUS_PUBLISHED);

        if (! empty($filters['q'])) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], strtolower(trim((string) $filters['q'])));
            $term = '%'.$escaped.'%';
            $query->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(title) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(subtitle) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(description) LIKE ?', [$term]);
            });
        }

        if (! empty($filters['category_id'])) {
            $catId = (int) $filters['category_id'];
            $childIds = LearningCategory::where('parent_id', $catId)
                ->where('is_active', true)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $query->whereIn('category_id', array_values(array_unique(array_merge([$catId], $childIds))));
        }

        if (! empty($filters['category_slug'])) {
            $cat = LearningCategory::where('slug', $filters['category_slug'])->first();
            if ($cat) {
                $childIds = LearningCategory::where('parent_id', $cat->id)
                    ->where('is_active', true)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
                $query->whereIn('category_id', array_values(array_unique(array_merge([(int) $cat->id], $childIds))));
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if (! empty($filters['level']) && $filters['level'] !== 'all') {
            $query->where('level', $filters['level']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['skill'])) {
            $query->whereHas('skills', function ($q) use ($filters) {
                $q->whereRaw('LOWER(skill_name) = ?', [strtolower(trim((string) $filters['skill']))]);
            });
        }

        if (isset($filters['min_price'])) {
            $query->whereRaw('COALESCE(discounted_price, price) >= ?', [(float) $filters['min_price']]);
        }

        if (isset($filters['max_price'])) {
            $query->whereRaw('COALESCE(discounted_price, price) <= ?', [(float) $filters['max_price']]);
        }

        if (isset($filters['min_rating'])) {
            $query->where('rating_avg', '>=', (float) $filters['min_rating']);
        }

        $sortBy = $filters['sort_by'] ?? ($filters['sort'] ?? 'popular');
        match ($sortBy) {
            'newest', 'latest' => $query->orderByDesc('published_at')->orderByDesc('id'),
            'price_low' => $query->orderByRaw('COALESCE(discounted_price, price) ASC'),
            'price_high' => $query->orderByRaw('COALESCE(discounted_price, price) DESC'),
            'rating' => $query->orderByDesc('rating_avg')->orderByDesc('rating_count'),
            default => $query->orderByDesc('is_featured')->orderByDesc('enrollments_count'),
        };

        return $query->paginate($perPage);
    }

    /**
     * Retrieve featured courses.
     */
    public function getFeaturedCourses(int $limit = 6): Collection
    {
        return LearningCourse::with(['instructor', 'category', 'skills'])
            ->where('status', LearningCourse::STATUS_PUBLISHED)
            ->where('is_featured', true)
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Retrieve popular courses based on real enrollments and ratings.
     */
    public function getPopularCourses(int $limit = 6): Collection
    {
        return LearningCourse::with(['instructor', 'category', 'skills'])
            ->where('status', LearningCourse::STATUS_PUBLISHED)
            ->orderByDesc('enrollments_count')
            ->orderByDesc('rating_avg')
            ->limit($limit)
            ->get();
    }

    /**
     * Get courses authored by an instructor.
     */
    public function getInstructorCourses(User $instructor, int $perPage = 15): LengthAwarePaginator
    {
        return LearningCourse::with(['category', 'skills'])
            ->where('instructor_id', $instructor->id)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Delete/soft-delete a course and synchronize category counts.
     */
    public function deleteCourse(LearningCourse $course): bool
    {
        return DB::transaction(function () use ($course) {
            $wasPublished = $course->status === LearningCourse::STATUS_PUBLISHED;
            $categoryId = $course->category_id;

            $deleted = (bool) $course->delete();

            if ($deleted && $wasPublished && $categoryId) {
                $this->recountCategoryCourses((int) $categoryId);
            }

            return $deleted;
        });
    }

    /**
     * Get course details with curriculum structure.
     */
    public function getCourseDetails(string|int $identifier, ?User $viewer = null): ?LearningCourse
    {
        $query = LearningCourse::with([
            'instructor.profile',
            'category',
            'skills',
            'sections' => function ($q) {
                $q->where('is_published', true)->orderBy('sort_order');
            },
            'sections.lessons' => function ($q) {
                $q->where('is_published', true)->orderBy('sort_order');
            },
            'reviews.user' => function ($q) {
                $q->latest()->limit(5);
            },
        ]);

        $course = is_numeric($identifier)
            ? $query->where('id', $identifier)->first()
            : $query->where('slug', $identifier)->first();

        if (! $course) {
            return null;
        }

        $isPrivilegedViewer = $viewer && ((int) $viewer->id === (int) $course->instructor_id || $viewer->isAdmin());
        if ($course->status !== LearningCourse::STATUS_PUBLISHED && ! $isPrivilegedViewer) {
            return null;
        }

        $hasFullCurriculumAccess = $isPrivilegedViewer || $course->isEnrolledByUser($viewer);
        if (! $hasFullCurriculumAccess) {
            foreach ($course->sections as $section) {
                foreach ($section->lessons as $lesson) {
                    if (! $lesson->is_preview) {
                        $lesson->setAttribute('video_url', null);
                        $lesson->setAttribute('content', null);
                    }
                }
            }
        }

        return $course;
    }

    /**
     * Create a new course by an authorized instructor.
     */
    public function createCourse(User $instructor, array $data): LearningCourse
    {
        return DB::transaction(function () use ($instructor, $data) {
            $slug = Str::slug($data['title']);
            if (LearningCourse::withTrashed()->where('slug', $slug)->exists()) {
                $slug .= '-'.Str::random(5);
            }

            $course = LearningCourse::create([
                'instructor_id' => $instructor->id,
                'category_id' => $data['category_id'] ?? null,
                'title' => $data['title'],
                'slug' => $slug,
                'subtitle' => $data['subtitle'] ?? null,
                'description' => $data['description'] ?? '',
                'level' => $data['level'] ?? 'all_levels',
                'language' => $data['language'] ?? 'en',
                'thumbnail_url' => $data['thumbnail_url'] ?? null,
                'promotional_video_url' => $data['promotional_video_url'] ?? null,
                'type' => $data['type'] ?? 'free',
                'price' => ($data['type'] ?? 'free') === 'free' ? 0.00 : ($data['price'] ?? 0.00),
                'discounted_price' => ($data['type'] ?? 'free') === 'free' ? null : ($data['discounted_price'] ?? null),
                'currency' => $data['currency'] ?? 'USD',
                'status' => LearningCourse::STATUS_DRAFT,
                'learning_objectives' => $data['learning_objectives'] ?? [],
                'prerequisites' => $data['prerequisites'] ?? [],
                'target_audience' => $data['target_audience'] ?? [],
            ]);

            if (! empty($data['skills']) && is_array($data['skills'])) {
                foreach ($data['skills'] as $skill) {
                    if (is_string($skill) && trim($skill) !== '') {
                        LearningCourseSkill::create([
                            'course_id' => $course->id,
                            'skill_name' => trim($skill),
                        ]);
                    }
                }
            }

            return $course->load(['skills', 'category']);
        });
    }

    /**
     * Update an existing course.
     */
    public function updateCourse(LearningCourse $course, array $data): LearningCourse
    {
        return DB::transaction(function () use ($course, $data) {
            $oldCategoryId = $course->category_id ? (int) $course->category_id : null;

            $course->update($data);

            if (isset($data['skills']) && is_array($data['skills'])) {
                $course->skills()->delete();
                foreach ($data['skills'] as $skill) {
                    if (is_string($skill) && trim($skill) !== '') {
                        LearningCourseSkill::create([
                            'course_id' => $course->id,
                            'skill_name' => trim($skill),
                        ]);
                    }
                }
            }

            $newCategoryId = $course->category_id ? (int) $course->category_id : null;
            if ($oldCategoryId && $oldCategoryId !== $newCategoryId) {
                $this->recountCategoryCourses($oldCategoryId);
            }
            if ($newCategoryId) {
                $this->recountCategoryCourses($newCategoryId);
            }

            return $course->fresh(['skills', 'category']);
        });
    }

    /**
     * Publish or submit course.
     */
    public function publishCourse(LearningCourse $course, bool $autoApprove = true): LearningCourse
    {
        return DB::transaction(function () use ($course, $autoApprove) {
            $status = $autoApprove ? LearningCourse::STATUS_PUBLISHED : LearningCourse::STATUS_PENDING;
            $course->update([
                'status' => $status,
                'published_at' => $autoApprove ? ($course->published_at ?? now()) : null,
            ]);

            if ($course->category_id) {
                $this->recountCategoryCourses((int) $course->category_id);
            }

            return $course->fresh();
        });
    }

    /**
     * Recalculate published courses count for a category deterministically.
     */
    public function recountCategoryCourses(int $categoryId): void
    {
        $publishedCount = LearningCourse::where('category_id', $categoryId)
            ->where('status', LearningCourse::STATUS_PUBLISHED)
            ->count();

        LearningCategory::where('id', $categoryId)->update([
            'courses_count' => $publishedCount,
        ]);
    }
}
