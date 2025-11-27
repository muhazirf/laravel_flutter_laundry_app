<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                    API Documentation
                </h2>
                <div class="flex space-x-2">
                    <a href="{{ $documentationUrl }}"
                       target="_blank"
                       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                        Open in New Tab
                    </a>
                </div>
            </div>

            <div class="prose dark:prose-invert max-w-none">
                <p class="text-gray-600 dark:text-gray-300">
                    Comprehensive API documentation for the Laundry Management System.
                    This documentation includes all available endpoints, authentication methods,
                    request/response formats, and usage examples.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center mb-4">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <h3 class="ml-3 text-lg font-semibold text-gray-900 dark:text-white">
                        Authentication
                    </h3>
                </div>
                <p class="text-gray-600 dark:text-gray-300 text-sm">
                    JWT Bearer tokens and API Key authentication methods available.
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center mb-4">
                    <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="ml-3 text-lg font-semibold text-gray-900 dark:text-white">
                        RESTful API
                    </h3>
                </div>
                <p class="text-gray-600 dark:text-gray-300 text-sm">
                    Standard REST endpoints with proper HTTP methods and status codes.
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center mb-4">
                    <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                        </svg>
                    </div>
                    <h3 class="ml-3 text-lg font-semibold text-gray-900 dark:text-white">
                        Interactive Testing
                    </h3>
                </div>
                <p class="text-gray-600 dark:text-gray-300 text-sm">
                    Try API endpoints directly from the documentation interface.
                </p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Quick Access
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-white mb-2">Authentication Endpoints</h4>
                        <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-300">
                            <li>• <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">POST /api/auth/login</code></li>
                            <li>• <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">POST /api/auth/register</code></li>
                            <li>• <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">GET /api/auth/me</code></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-white mb-2">Business Endpoints</h4>
                        <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-300">
                            <li>• <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">GET /api/auth/get-outlets</code></li>
                            <li>• <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">POST /api/auth/generate-api-key</code></li>
                            <li>• <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">GET /api/status</code></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        @if(app()->environment('local'))
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                        Development Mode
                    </h3>
                    <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                        <p>
                            API documentation is automatically generated. To regenerate after changes:
                        </p>
                        <code class="mt-1 block bg-yellow-100 dark:bg-yellow-900/30 px-2 py-1 rounded">
                            php artisan scribe:generate
                        </code>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</x-filament-panels::page>