<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ApiResponseService;
use Illuminate\Http\JsonResponse;

abstract class BaseApiController extends Controller
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
    protected function success($data = null, string $message = 'Success', $meta = null, int $status = 200): JsonResponse
    {
        return ApiResponseService::success($data, $message, $meta, $status);
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
    protected function error(string $message = 'Error', $errors = null, $data = null, int $status = 400): JsonResponse
    {
        return ApiResponseService::error($message, $errors, $data, $status);
    }

    /**
     * Return a created response (201)
     *
     * @param mixed $data The response data
     * @param string $message Success message
     * @return JsonResponse
     */
    protected function created($data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return ApiResponseService::created($data, $message);
    }

    /**
     * Return a not found response (404)
     *
     * @param string $message Error message
     * @return JsonResponse
     */
    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return ApiResponseService::notFound($message);
    }

    /**
     * Return an unauthorized response (401)
     *
     * @param string $message Error message
     * @return JsonResponse
     */
    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return ApiResponseService::unauthorized($message);
    }

    /**
     * Return a forbidden response (403)
     *
     * @param string $message Error message
     * @return JsonResponse
     */
    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return ApiResponseService::forbidden($message);
    }

    /**
     * Return an internal server error response (500)
     *
     * @param string $message Error message
     * @param mixed $errors Error details
     * @return JsonResponse
     */
    protected function serverError(string $message = 'Internal Server Error', $errors = null): JsonResponse
    {
        return ApiResponseService::serverError($message, $errors);
    }

    /**
     * Return a validation error response (422)
     *
     * @param mixed $errors Validation errors
     * @param string $message Error message
     * @return JsonResponse
     */
    protected function validation($errors, string $message = 'Validation failed'): JsonResponse
    {
        return ApiResponseService::validation($errors, $message);
    }
}