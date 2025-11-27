<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;

class ApiResponseService
{
    /**
     * Return a successful JSON response
     *
     * @param mixed $data The response data
     * @param string $message Success message
     * @param mixed $meta Additional meta information
     * @param int $status HTTP status code
     * @return JsonResponse
     */
    public static function success($data = null, string $message = 'Success', $meta = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
            'meta' => $meta
        ], $status);
    }

    /**
     * Return an error JSON response
     *
     * @param string $message Error message
     * @param mixed $errors Error details
     * @param mixed $data Additional response data
     * @param int $status HTTP status code
     * @return JsonResponse
     */
    public static function error(string $message = 'Error', $errors = null, $data = null, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
            'errors' => $errors,
            'meta' => null
        ], $status);
    }

    /**
     * Return a validation error JSON response
     *
     * @param mixed $errors Validation errors
     * @param string $message Error message
     * @return JsonResponse
     */
    public static function validation($errors, string $message = 'Validation failed'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
            'meta' => null
        ], 422);
    }

    /**
     * Return a created response (201)
     *
     * @param mixed $data The response data
     * @param string $message Success message
     * @return JsonResponse
     */
    public static function created($data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return self::success($data, $message, null, 201);
    }

    /**
     * Return a not found response (404)
     *
     * @param string $message Error message
     * @return JsonResponse
     */
    public static function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return self::error($message, null, null, 404);
    }

    /**
     * Return an unauthorized response (401)
     *
     * @param string $message Error message
     * @return JsonResponse
     */
    public static function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return self::error($message, null, null, 401);
    }

    /**
     * Return a forbidden response (403)
     *
     * @param string $message Error message
     * @return JsonResponse
     */
    public static function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return self::error($message, null, null, 403);
    }

    /**
     * Return an internal server error response (500)
     *
     * @param string $message Error message
     * @param mixed $errors Error details
     * @return JsonResponse
     */
    public static function serverError(string $message = 'Internal Server Error', $errors = null): JsonResponse
    {
        return self::error($message, $errors, null, 500);
    }
}