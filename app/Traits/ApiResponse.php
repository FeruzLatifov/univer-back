<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * API Response Trait
 *
 * Best Practice: Consistent API response format across all endpoints
 * Usage: use ApiResponse; in controllers
 */
trait ApiResponse
{
    /**
     * Success response
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $code
     * @return JsonResponse
     */
    protected function successResponse($data = null, ?string $message = null, int $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
        ];

        if ($message) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    /**
     * Error response
     *
     * @param string $message
     * @param int $code
     * @param mixed $errors
     * @return JsonResponse
     */
    protected function errorResponse(string $message, int $code = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Paginated response
     *
     * @param LengthAwarePaginator $paginator
     * @param mixed $data
     * @param string|null $message
     * @return JsonResponse
     */
    protected function paginatedResponse(LengthAwarePaginator $paginator, $data, ?string $message = null): JsonResponse
    {
        $response = [
            'success' => true,
        ];

        if ($message) {
            $response['message'] = $message;
        }

        $response['data'] = $data;
        $response['meta'] = [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];

        $response['links'] = [
            'first' => $paginator->url(1),
            'last' => $paginator->url($paginator->lastPage()),
            'prev' => $paginator->previousPageUrl(),
            'next' => $paginator->nextPageUrl(),
        ];

        return response()->json($response);
    }

    /**
     * Created response (201)
     *
     * @param mixed $data
     * @param string|null $message
     * @return JsonResponse
     */
    protected function createdResponse($data, ?string $message = null): JsonResponse
    {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * No content response (204)
     *
     * @return JsonResponse
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Not found response (404)
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function notFoundResponse(string $message = 'Ma\'lumot topilmadi'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Validation error response (422)
     *
     * @param array $errors
     * @param string $message
     * @return JsonResponse
     */
    protected function validationErrorResponse(array $errors, string $message = 'Validation error'): JsonResponse
    {
        return $this->errorResponse($message, 422, $errors);
    }

    /**
     * Unauthorized response (401)
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function unauthorizedResponse(string $message = 'Autentifikatsiya talab qilinadi'): JsonResponse
    {
        return $this->errorResponse($message, 401);
    }

    /**
     * Forbidden response (403)
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function forbiddenResponse(string $message = 'Sizda bu amalni bajarish uchun ruxsat yo\'q'): JsonResponse
    {
        return $this->errorResponse($message, 403);
    }

    /**
     * Server error response (500)
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function serverErrorResponse(string $message = 'Server xatosi'): JsonResponse
    {
        return $this->errorResponse($message, 500);
    }
}
