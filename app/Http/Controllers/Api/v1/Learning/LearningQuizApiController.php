<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1\Learning;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\SubmitQuizAttemptRequest;
use App\Models\LearningQuiz;
use App\Models\LearningQuizAttempt;
use App\Services\Learning\QuizService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LearningQuizApiController extends Controller
{
    public function __construct(
        protected QuizService $quizService
    ) {}

    /**
     * GET /api/v1/learning/quizzes/{id}
     */
    public function show(int $id): JsonResponse
    {
        $quiz = LearningQuiz::findOrFail($id);
        $data = $this->quizService->getQuizForStudent($quiz);

        return $this->successResponse($data, 'Quiz retrieved successfully.');
    }

    /**
     * POST /api/v1/learning/quizzes/{id}/attempt
     */
    public function startAttempt(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $quiz = LearningQuiz::findOrFail($id);

        try {
            $attempt = $this->quizService->startAttempt($user, $quiz);

            return $this->successResponse($attempt, 'Quiz attempt started successfully.', 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * POST /api/v1/learning/quizzes/attempts/{attemptId}/submit
     */
    public function submitAttempt(SubmitQuizAttemptRequest $request, int $attemptId): JsonResponse
    {
        $user = $request->user();
        $attempt = LearningQuizAttempt::with('quiz')->findOrFail($attemptId);

        if ($attempt->user_id !== $user->id) {
            return $this->errorResponse('Unauthorized attempt access.', 403);
        }

        if ($attempt->submitted_at) {
            return $this->errorResponse('This attempt has already been submitted.', 422);
        }

        $graded = $this->quizService->submitQuiz($attempt, $request->input('answers'));

        return $this->successResponse($graded, 'Quiz submitted and graded successfully.');
    }

    /**
     * GET /api/v1/learning/quiz-attempts/{attemptId}
     */
    public function showAttempt(Request $request, int $attemptId): JsonResponse
    {
        $user = $request->user();
        $attempt = LearningQuizAttempt::findOrFail($attemptId);

        try {
            $data = $this->quizService->getAttempt($user, $attempt);

            return $this->successResponse($data, 'Attempt retrieved.');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 403);
        }
    }

    /**
     * POST /api/v1/learning/quiz-attempts/{attemptId}/answers
     */
    public function saveAnswers(Request $request, int $attemptId): JsonResponse
    {
        $user = $request->user();
        $attempt = LearningQuizAttempt::findOrFail($attemptId);

        if ($attempt->user_id !== $user->id && ! $user->hasRole(['ADMIN', 'SUPER_ADMIN'])) {
            return $this->errorResponse('Unauthorized attempt access.', 403);
        }

        try {
            $this->quizService->saveInterimAnswers($attempt, (array) $request->input('answers', []));

            return $this->successResponse(null, 'Answers saved.');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * GET /api/v1/learning/quiz-attempts/{attemptId}/result
     */
    public function attemptResult(Request $request, int $attemptId): JsonResponse
    {
        $user = $request->user();
        $attempt = LearningQuizAttempt::with('quiz')->findOrFail($attemptId);

        try {
            $data = $this->quizService->getAttemptResult($user, $attempt);

            return $this->successResponse($data, 'Attempt result retrieved.');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 403);
        }
    }
}
