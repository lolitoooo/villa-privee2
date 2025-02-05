<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

class ErrorController extends AbstractController
{
    public function __construct(private Environment $twig)
    {
    }

    public function show(Request $request, FlattenException $exception): Response
    {
        $method = $request->getMethod();
        $statusCode = $exception->getStatusCode();
        $statusText = Response::$statusTexts[$statusCode] ?? 'Unknown Error';

        $template = $statusCode === 404 ? 'error404.html.twig' : 'error.html.twig';

        return new Response(
            $this->twig->render(
                "@Twig/Exception/$template",
                [
                    'status_code' => $statusCode,
                    'status_text' => $statusText,
                    'exception' => $exception,
                    'method' => $method,
                ]
            ),
            $statusCode
        );
    }
}