<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\LearningEnrollment;
use App\Models\LearningLessonProgress;
use App\Models\LearningQuiz;
use App\Models\LearningQuizAnswer;
use App\Models\LearningQuizAttempt;
use App\Models\LearningQuizOption;
use App\Models\LearningQuizQuestion;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class QuizService
{
    /**
     * Get quiz details with questions sanitized (is_correct stripped).
     */
    public function getQuizForStudent(LearningQuiz $quiz): array
    {
        $questions = $quiz->questions()->with(['options'])->get();

        return [
            'id' => $quiz->id,
            'lesson_id' => $quiz->lesson_id,
            'title' => $quiz->title,
            'instructions' => $quiz->instructions,
            'time_limit_minutes' => $quiz->time_limit_minutes,
            'passing_score' => $quiz->passing_score,
            'max_attempts' => $quiz->max_attempts,
            'total_questions' => $questions->count(),
            'questions' => $questions->map(function (LearningQuizQuestion $q) {
                return [
                    'id' => $q->id,
                    'question' => $q->question,
                    'type' => $q->type,
                    'points' => $q->points,
                    'options' => $q->options->map(function (LearningQuizOption $opt) {
                        return [
                            'id' => $opt->id,
                            'option_text' => $opt->option_text,
                        ];
                    })->values(),
                ];
            })->values(),
        ];
    }

    /**
     * Start a new quiz attempt with row-level lock against max_attempts races.
     */
    public function startAttempt(User $user, LearningQuiz $quiz): LearningQuizAttempt
    {
        return DB::transaction(function () use ($user, $quiz) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            $pastAttemptsCount = LearningQuizAttempt::where('quiz_id', $quiz->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->count();

            if ($quiz->max_attempts > 0 && $pastAttemptsCount >= $quiz->max_attempts) {
                throw new Exception("Maximum allowed attempts ({$quiz->max_attempts}) reached.");
            }

            return LearningQuizAttempt::create([
                'quiz_id' => $quiz->id,
                'user_id' => $user->id,
                'attempt_number' => $pastAttemptsCount + 1,
                'started_at' => now(),
            ]);
        });
    }

    /**
     * Submit and grade quiz answers.
     */
    public function submitQuiz(
        LearningQuizAttempt $attempt,
        array $answers
    ): LearningQuizAttempt {
        return DB::transaction(function () use ($attempt, $answers) {
            $lockedAttempt = LearningQuizAttempt::with('quiz.course')->whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            if ($lockedAttempt->submitted_at) {
                throw new Exception('This attempt has already been submitted.');
            }

            $quiz = $lockedAttempt->quiz;
            $questions = $quiz->questions()->with('options')->get()->keyBy('id');

            $totalPossiblePoints = 0;
            $earnedPoints = 0;

            foreach ($questions as $qId => $question) {
                $totalPossiblePoints += $question->points;
                $submittedAnswer = $answers[$qId] ?? null;

                $isCorrect = false;
                $pointsAwarded = 0;
                $selectedOptionIds = [];
                $textAnswer = null;

                if ($submittedAnswer !== null) {
                    if (is_array($submittedAnswer)) {
                        $selectedOptionIds = array_map('intval', $submittedAnswer);
                        $correctOptionIds = $question->options->where('is_correct', true)->pluck('id')->map(fn ($id) => (int) $id)->all();
                        sort($correctOptionIds);
                        sort($selectedOptionIds);
                        $isCorrect = ($correctOptionIds === $selectedOptionIds);
                    } elseif (is_numeric($submittedAnswer)) {
                        $selectedOptionId = (int) $submittedAnswer;
                        $selectedOptionIds = [$selectedOptionId];
                        $option = $question->options->firstWhere('id', $selectedOptionId);
                        $isCorrect = (bool) ($option && $option->is_correct);
                    } elseif (is_string($submittedAnswer)) {
                        $textAnswer = trim($submittedAnswer);
                        $correctTexts = $question->options->where('is_correct', true)->pluck('option_text')->all();
                        foreach ($correctTexts as $cText) {
                            if (strcasecmp(trim($cText), $textAnswer) === 0) {
                                $isCorrect = true;
                                break;
                            }
                        }
                    }
                }

                if ($isCorrect) {
                    $pointsAwarded = $question->points;
                    $earnedPoints += $pointsAwarded;
                }

                LearningQuizAnswer::updateOrCreate(
                    [
                        'attempt_id' => $lockedAttempt->id,
                        'question_id' => $question->id,
                    ],
                    [
                        'selected_option_ids' => $selectedOptionIds,
                        'text_answer' => $textAnswer,
                        'is_correct' => $isCorrect,
                        'points_awarded' => $pointsAwarded,
                    ]
                );
            }

            $scorePercentage = $totalPossiblePoints > 0
                ? round(($earnedPoints / $totalPossiblePoints) * 100, 2)
                : 100.00;

            $passed = ($scorePercentage >= $quiz->passing_score);

            $lockedAttempt->update([
                'score' => $scorePercentage,
                'total_points' => $totalPossiblePoints,
                'earned_points' => $earnedPoints,
                'passed' => $passed,
                'submitted_at' => now(),
            ]);

            // If passed, mark quiz lesson as completed and recalculate overall course progress
            if ($passed && $quiz->lesson_id) {
                $enrollment = LearningEnrollment::where('course_id', $quiz->course_id)
                    ->where('user_id', $lockedAttempt->user_id)
                    ->whereIn('status', [LearningEnrollment::STATUS_ACTIVE, LearningEnrollment::STATUS_COMPLETED])
                    ->lockForUpdate()
                    ->first();

                LearningLessonProgress::updateOrCreate(
                    [
                        'user_id' => $lockedAttempt->user_id,
                        'lesson_id' => $quiz->lesson_id,
                    ],
                    [
                        'enrollment_id' => $enrollment?->id,
                        'course_id' => $quiz->course_id,
                        'is_completed' => true,
                        'completed_at' => now(),
                    ]
                );

                if ($enrollment && $quiz->course) {
                    $student = User::find($lockedAttempt->user_id);
                    if ($student) {
                        app(LearningProgressService::class)->recalculateCourseProgress($enrollment, $student, $quiz->course);
                    }
                }
            }

            return $lockedAttempt->fresh(['answers']);
        });
    }

    /**
     * Get attempt details for the student.
     */
    public function getAttempt(User $user, LearningQuizAttempt $attempt): array
    {
        if ((int) $attempt->user_id !== (int) $user->id && ! $user->isAdmin()) {
            throw new Exception('Unauthorized attempt access.');
        }

        $quiz = $attempt->quiz;
        $quizData = $this->getQuizForStudent($quiz);

        return [
            'attempt' => $attempt,
            'quiz' => $quizData,
        ];
    }

    /**
     * Save interim answers without final grading.
     */
    public function saveInterimAnswers(LearningQuizAttempt $attempt, array $answers): void
    {
        if ($attempt->submitted_at) {
            throw new Exception('Attempt already finalized and submitted.');
        }

        DB::transaction(function () use ($attempt, $answers) {
            $validQuestionIds = $attempt->quiz
                ? $attempt->quiz->questions()->pluck('id')->map(fn ($id) => (int) $id)->all()
                : [];

            foreach ($answers as $qId => $val) {
                $questionId = (int) $qId;
                if (! empty($validQuestionIds) && ! in_array($questionId, $validQuestionIds, true)) {
                    continue;
                }

                LearningQuizAnswer::updateOrCreate(
                    [
                        'attempt_id' => $attempt->id,
                        'question_id' => $questionId,
                    ],
                    [
                        'selected_option_ids' => is_array($val) ? array_map('intval', $val) : (is_numeric($val) ? [(int) $val] : []),
                        'text_answer' => is_string($val) ? $val : null,
                    ]
                );
            }
        });
    }

    /**
     * Get final attempt result with pass/fail and scores.
     */
    public function getAttemptResult(User $user, LearningQuizAttempt $attempt): array
    {
        if ((int) $attempt->user_id !== (int) $user->id && ! $user->isAdmin()) {
            throw new Exception('Unauthorized attempt access.');
        }

        if (! $attempt->submitted_at) {
            throw new Exception('Attempt has not yet been submitted or graded.');
        }

        return [
            'attempt_id' => $attempt->id,
            'quiz_id' => $attempt->quiz_id,
            'score' => $attempt->score,
            'earned_points' => $attempt->earned_points,
            'total_points' => $attempt->total_points,
            'passed' => $attempt->passed,
            'passing_score' => $attempt->quiz?->passing_score ?? 70,
            'submitted_at' => $attempt->submitted_at?->toIso8601String(),
            'answers' => $attempt->answers,
        ];
    }
}
