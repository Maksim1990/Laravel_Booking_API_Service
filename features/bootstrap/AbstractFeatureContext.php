<?php

use App\Exceptions\BehatRuntimeException;
use Behat\Behat\Context\Context;
use Imbo\BehatApiExtension\Context\ApiContext;

/**
 * Defines application features from the specific context.
 */
abstract class AbstractFeatureContext extends ApiContext implements Context
{
    protected const API_VERSION_PREFIX = '/api';

    protected string $behatToken = '';

    protected function getResponseBodyContent()
    {
        $this->requireResponse();
        $response = json_decode($this->response->getBody()->getContents());
        if (isset($response->errors)) {
            throw new BehatRuntimeException($response->errors->message, $response->code);
        }

        return $response;
    }

    protected function getBehatToken(): string
    {
        return $this->behatToken;
    }
}
