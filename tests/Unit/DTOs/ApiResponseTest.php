<?php

declare(strict_types=1);

use Lynkbyte\EvolutionApi\DTOs\ApiResponse;
use Lynkbyte\EvolutionApi\Exceptions\EvolutionApiException;

describe('ApiResponse', function () {
    describe('constructor', function () {
        it('creates a response with all properties', function () {
            $response = new ApiResponse(
                success: true,
                statusCode: 200,
                data: ['key' => 'value'],
                message: 'Success',
                headers: ['Content-Type' => 'application/json'],
                responseTime: 0.123
            );

            expect($response->success)->toBeTrue();
            expect($response->statusCode)->toBe(200);
            expect($response->data)->toBe(['key' => 'value']);
            expect($response->message)->toBe('Success');
            expect($response->headers)->toBe(['Content-Type' => 'application/json']);
            expect($response->responseTime)->toBe(0.123);
        });

        it('creates a response with default values', function () {
            $response = new ApiResponse(
                success: true,
                statusCode: 200
            );

            expect($response->data)->toBe([]);
            expect($response->message)->toBeNull();
            expect($response->headers)->toBe([]);
            expect($response->responseTime)->toBeNull();
        });
    });

    describe('success', function () {
        it('creates a successful response', function () {
            $response = ApiResponse::success(
                data: ['id' => 123],
                statusCode: 201,
                message: 'Created'
            );

            expect($response->success)->toBeTrue();
            expect($response->statusCode)->toBe(201);
            expect($response->data)->toBe(['id' => 123]);
            expect($response->message)->toBe('Created');
        });

        it('uses default values when not specified', function () {
            $response = ApiResponse::success();

            expect($response->success)->toBeTrue();
            expect($response->statusCode)->toBe(200);
            expect($response->data)->toBe([]);
            expect($response->message)->toBeNull();
        });

        it('includes response time when provided', function () {
            $response = ApiResponse::success(responseTime: 0.5);

            expect($response->responseTime)->toBe(0.5);
        });
    });

    describe('failure', function () {
        it('creates a failed response', function () {
            $response = ApiResponse::failure(
                message: 'Something went wrong',
                statusCode: 500,
                data: ['error_code' => 'SERVER_ERROR']
            );

            expect($response->success)->toBeFalse();
            expect($response->statusCode)->toBe(500);
            expect($response->message)->toBe('Something went wrong');
            expect($response->data)->toBe(['error_code' => 'SERVER_ERROR']);
        });

        it('uses default status code 400 when not specified', function () {
            $response = ApiResponse::failure(message: 'Bad request');

            expect($response->statusCode)->toBe(400);
        });
    });

    describe('isSuccessful', function () {
        it('returns true for successful responses', function () {
            $response = ApiResponse::success();

            expect($response->isSuccessful())->toBeTrue();
        });

        it('returns false for failed responses', function () {
            $response = ApiResponse::failure(message: 'Error');

            expect($response->isSuccessful())->toBeFalse();
        });
    });

    describe('isFailed', function () {
        it('returns false for successful responses', function () {
            $response = ApiResponse::success();

            expect($response->isFailed())->toBeFalse();
        });

        it('returns true for failed responses', function () {
            $response = ApiResponse::failure(message: 'Error');

            expect($response->isFailed())->toBeTrue();
        });
    });

    describe('get', function () {
        it('returns value for existing key', function () {
            $response = ApiResponse::success(data: ['name' => 'John', 'age' => 30]);

            expect($response->get('name'))->toBe('John');
            expect($response->get('age'))->toBe(30);
        });

        it('returns default for missing key', function () {
            $response = ApiResponse::success(data: ['name' => 'John']);

            expect($response->get('email'))->toBeNull();
            expect($response->get('email', 'default@example.com'))->toBe('default@example.com');
        });
    });

    describe('getData', function () {
        it('returns all data', function () {
            $data = ['name' => 'John', 'age' => 30];
            $response = ApiResponse::success(data: $data);

            expect($response->getData())->toBe($data);
        });

        it('returns empty array when no data', function () {
            $response = ApiResponse::success();

            expect($response->getData())->toBe([]);
        });
    });

    describe('toArray', function () {
        it('converts response to array', function () {
            $response = new ApiResponse(
                success: true,
                statusCode: 200,
                data: ['key' => 'value'],
                message: 'Success',
                responseTime: 0.123
            );

            $array = $response->toArray();

            expect($array)->toBe([
                'success' => true,
                'status_code' => 200,
                'data' => ['key' => 'value'],
                'message' => 'Success',
                'response_time' => 0.123,
            ]);
        });
    });

    describe('throw', function () {
        it('returns self for successful responses', function () {
            $response = ApiResponse::success();

            $result = $response->throw();

            expect($result)->toBe($response);
        });

        it('throws exception for failed responses', function () {
            $response = ApiResponse::failure(
                message: 'Not Found',
                statusCode: 404,
                data: ['error' => 'Resource not found']
            );

            $response->throw();
        })->throws(EvolutionApiException::class);

        it('can be chained after success', function () {
            $response = ApiResponse::success(data: ['id' => 123]);

            $id = $response->throw()->get('id');

            expect($id)->toBe(123);
        });
    });
});
