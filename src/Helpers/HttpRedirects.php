<?php

namespace SMIT\SDK\Helpers;

trait HttpRedirects
{
    private $redirectUrl;

    private $queryParameters = [];

    public function setRedirectUrl(string $url)
    {
        $this->redirectUrl = $url;
    }

    public function getRedirectUrl()
    {
        if (!empty($this->queryParameters)) {
            return sprintf('%s?%s', $this->redirectUrl, $this->getQueryString());
        }

        return $this->redirectUrl;
    }

    public function setQueryParameters(array $parameters)
    {
        $this->queryParameters = $parameters;

        return $this;
    }

    public function getQueryString()
    {
        return http_build_query($this->queryParameters);
    }

    public function redirect(string $url = null, array $queryParameters = [])
    {
        if (!empty($queryParameters)) {
            $this->setQueryParameters($queryParameters);
        }

        if (!is_null($url)) {
            $this->setRedirectUrl($url);
        }

        return header(sprintf('Location: %s', $this->getRedirectUrl()));
    }
}
