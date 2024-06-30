<?php

namespace GearDev\LaravelBridge\Client;

use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Octane\Contracts\Client;
use Laravel\Octane\Contracts\ServesStaticFiles;
use Laravel\Octane\MimeType;
use Laravel\Octane\Octane;
use Laravel\Octane\OctaneResponse;
use Laravel\Octane\RequestContext;
use Swow\Psr7\Message\Response;
use Swow\Psr7\Server\ServerConnection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class SwowHttpPhpClient implements Client, ServesStaticFiles
{

    public function marshalRequest(RequestContext $context): array
    {
        /** @var ServerConnection $connection */
        $connection = $context->swowConnection;
        $request = $connection->recvHttpRequest();
        $convertedHeaders = [];
        foreach ($request->getHeaders() as $key => $header) {
            $convertedHeaders['HTTP_' . $key] = $header[0];
        }
        $serverParams = array_merge([
            'REQUEST_URI' => $request->getUri()->getPath(),
            'REQUEST_METHOD' => $request->getMethod(),
            'QUERY_STRING' => $request->getUri()->getQuery(),
        ], $request->getServerParams(), $convertedHeaders);
        $parsedBody = $this->buildNestedArrayFromParsedBody($request->getParsedBody());
        $symfonyRequest = new \Symfony\Component\HttpFoundation\Request(
            query: $request->getQueryParams(),
            request: $parsedBody,
            attributes: [...$request->getAttributes(), 'transport'=>'http'],
            cookies: $request->getCookieParams(),
            files: $request->getUploadedFiles(),
            server: $serverParams,
            content: $request->getBody()->getContents()
        );

        $laravelRequest = Request::createFromBase($symfonyRequest);

        return [
          $laravelRequest,
          $context
        ];
    }

    public static function buildNestedArrayFromParsedBody(array $parsedBody) {

        $result = [];
        foreach ($parsedBody as $key => $value) {
            $keys = explode('[', $key);
            $keys = array_map(fn($key) => str_replace(']', '', $key), $keys);
            $nestedArray = [];
            $nestedArray[$keys[count($keys)-1]] = $value;
            for ($i = count($keys)-2; $i >= 0; $i--) {
                $nestedArray = [$keys[$i] => $nestedArray];
            }
            $result = array_merge_recursive($result, $nestedArray);
        }
        return $result;
    }

    public function respond(RequestContext $context, OctaneResponse $response): void
    {
        /** @var ServerConnection $connection */
        $connection = $context->swowConnection;
        if ($response->response instanceof BinaryFileResponse) {
            $swowResponse = $this->createBinaryFileResponse($response->response);
        } elseif ($response->response instanceof \Illuminate\Http\Response) {
            $swowResponse = $this->createDefaultResponse($response->response);
        } elseif ($response->response instanceof RedirectResponse) {
            $swowResponse = $this->createRedirectResponse($response->response);
        } elseif ($response->response instanceof JsonResponse) {
            $swowResponse = $this->createJsonResponse($response->response);
        } else {
            $connection->error(510, 'Response Type is not supported: '.get_class($response), close: true);
        }
        $connection->sendHttpResponse($swowResponse);
        $connection->close();
    }

    public function createRedirectResponse(RedirectResponse $response): Response
    {
        $swowResponse = new Response();
        $swowResponse->setBody($response->getContent());
        $swowResponse->setStatus($response->getStatusCode());
        $swowResponse->setHeaders($response->headers->all());
        $swowResponse->setProtocolVersion($response->getProtocolVersion());
        return $swowResponse;
    }

    public function createJsonResponse(JsonResponse $response): Response
    {
        $swowResponse = new Response();
        $swowResponse->setBody($response->getContent());
        $swowResponse->setStatus($response->getStatusCode());
        $swowResponse->setHeaders($response->headers->all());
        $swowResponse->setProtocolVersion($response->getProtocolVersion());
        return $swowResponse;
    }

    public function createDefaultResponse(\Illuminate\Http\Response $response): Response
    {
        $swowResponse = new Response();
        $swowResponse->setBody($response->getContent());
        $swowResponse->setStatus($response->getStatusCode());
        $swowResponse->setHeaders($response->headers->all());
        $swowResponse->setProtocolVersion($response->getProtocolVersion());
        return $swowResponse;
    }

    private function createBinaryFileResponse(BinaryFileResponse $response)
    {
        $file = $response->getFile();
        $swowResponse = new Response();
        $swowResponse->setBody($file->getContent());
        $swowResponse->setStatus($response->getStatusCode());
        $swowResponse->setHeaders($response->headers->all());
        $swowResponse->setProtocolVersion($response->getProtocolVersion());
        return $swowResponse;
    }

    public function error(Throwable $e, Application $app, Request $request, RequestContext $context): void
    {
        /** @var ServerConnection $connection */
        $connection = $context->swowConnection;
        if ($e instanceof HttpExceptionInterface) {
            $code = $e->getStatusCode();
        } else {
            $code = 500;
        }

        $swowResponse = new Response();
        $swowResponse->setBody(Octane::formatExceptionForClient($e, $app->make('config')->get('app.debug')));
        $swowResponse->setStatus($code);
        $swowResponse->setHeaders([
            'Content-Type' => 'text/plain'
        ]);
        $swowResponse->setProtocolVersion('1.1');
        $connection->sendHttpResponse($swowResponse);
        $connection->close();

    }

    public function canServeRequestAsStaticFile(Request $request, RequestContext $context): bool
    {
        if (str_starts_with($request->path(), '/build')) {
            return true;
        } elseif (str_starts_with($request->path(), '/vendor')) {
            return true;
        }

        return false;
    }

    public function serveStaticFile(Request $request, RequestContext $context): void
    {
        $swowResponse = new Response();
        if (file_exists(public_path($request->path()))) {
            $file = new File(public_path($request->path()));
            $contentOfFIle = $file->getContent();
            $swowResponse->setBody($contentOfFIle);
            $swowResponse->setStatus(200);
            $swowResponse->setHeaders([
                'Content-Type' => MimeType::get(pathinfo($request->path(), PATHINFO_EXTENSION))
            ]);

            $swowResponse->setProtocolVersion('1.1');
        } else {
            $swowResponse->setStatus(404);
            $swowResponse->setBody('File not found');
            $swowResponse->setHeaders([]);
            $swowResponse->setProtocolVersion('1.1');
        }
        $connection = $context->swowConnection;
        $connection->sendHttpResponse($swowResponse);
        $connection->close();
    }
}