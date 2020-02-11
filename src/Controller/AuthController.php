<?php

/**
 * MIT License
 *
 * Copyright (c) 2020 Wolf Utz<wpu@hotmail.de>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

declare(strict_types=1);

namespace App\Controller;

use App\Annotation\ControllerAnnotation;
use App\Auth\JsonWebTokenAuth;
use App\Service\ConsumerValidationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController
{
    private JsonWebTokenAuth $auth;

    private ConsumerValidationService $consumerValidationService;

    public function __construct(JsonWebTokenAuth $auth, ConsumerValidationService $consumerValidationService)
    {
        $this->auth = $auth;
        $this->consumerValidationService = $consumerValidationService;
    }

    /**
     * @ControllerAnnotation(route="/api/tokens", method="post", protected=false)
     */
    public function newTokenAction(Request $request, Response $response): Response
    {
        if (!$this->consumerValidationService->isValid($request)) {
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401, 'Unauthorized');
        }
        $result = [
            'access_token' => $this->auth->createJwt([]),
            'token_type' => 'Bearer',
            'expires_in' => $this->auth->getLifetime(),
        ];
        $response->getBody()->write((string) json_encode($result));
        $response = $response->withStatus(201)->withHeader('Content-type', 'application/json');

        return $response;
    }
}
