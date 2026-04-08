<?php

namespace ApiBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

trait ApiResponseTrait
{
    private int $statusCode = Response::HTTP_OK;

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode): static
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    public function respond($data, $headers = []): JsonResponse
    {
        return $this->json($data, $this->getStatusCode(), $headers);
    }

    public function respondBadRequest($message = 'Bad request.', $errors = []): JsonResponse
    {
        return $this
            ->setStatusCode(Response::HTTP_BAD_REQUEST)
            ->respondWithError($message, $errors)
        ;
    }

    public function respondInternalError($message = 'Internal Error!'): JsonResponse
    {
        return $this
            ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->respondWithError($message)
        ;
    }

    public function respondNotFound($message = 'Not found!'): JsonResponse
    {
        return $this
            ->setStatusCode(Response::HTTP_NOT_FOUND)
            ->respondWithError($message)
        ;
    }

    public function respondUnauthorized($message = 'Unauthorized'): JsonResponse
    {
        return $this
            ->setStatusCode(Response::HTTP_UNAUTHORIZED)
            ->respondWithError($message)
        ;
    }

    public function respondWithError($message, array $errors = []): JsonResponse
    {
        $payload = [
            'code' => $this->getStatusCode(),
            'success' => false,
            'message' => $message,
            'errors' => [],
        ];

        if ([] !== $errors) {
            $payload['errors'] = ['children' => $errors];
        }

        return $this->respond($payload);
    }

    protected function respondSuccess($message = '', $result = null): JsonResponse
    {
        $response = ['success' => true];

        if ($message) {
            $response['message'] = $message;
        }

        if ($result) {
            $response['result'] = $result;
        }

        return $this->respond($response);
    }

    protected function respondFile($fileName, $fileData, array $headers = []): Response
    {
        $response = new Response($fileData, Response::HTTP_OK);

        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $fileName)
        );

        foreach ($headers as $key => $header) {
            $response->headers->set($key, $header);
        }

        return $response;
    }

    protected function respondXML($payload): Response
    {
        $response = new Response(html_entity_decode((string) $payload), Response::HTTP_OK);

        $response->headers->set('Content-Type', 'text/xml');

        return $response;
    }

    protected function respondJSON($payload): Response
    {
        $response = new Response($payload);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function respondAccessDenied(string $message = 'Access denied.', array $errors = []): JsonResponse
    {
        return $this
            ->setStatusCode(Response::HTTP_FORBIDDEN)
            ->respondWithError($message, $errors)
        ;
    }
}
