<?php

namespace Thinktomorrow\Trader\Integration\Laravel\Common;

use Illuminate\Cookie\CookieJar;
use Illuminate\Http\Request;

abstract class CookieSource
{
    /** @var string */
    protected $value;

    /** @var CookieJar */
    private $cookieJar;

    /** @var Request */
    private $request;

    public function __construct(Request $request, CookieJar $cookieJar)
    {
        // TODO: replace with getCookieKey protected abstract as well as getLifetime
        if(!isset($this->cookieKey) || !$this->cookieKey)
        {
            throw new \DomainException('A CookieValue class requires a cookieKey property value. Did you perhaps forget to set it?');
        }

        if(!isset($this->lifetime) || !$this->lifetime)
        {
            throw new \DomainException('A CookieValue class requires a lifetime property value. Did you perhaps forget to set it?');
        }

        $this->request = $request;
        $this->cookieJar = $cookieJar;

        /**
         * Since this class uses cookies, make sure the EncryptCookies
         * Middleware is kicked in before you instantiate this class
         */
        $this->value = $this->decodeCookieValue($this->request->cookie($this->cookieKey));
    }

    public function exists(): bool
    {
        return !!$this->value;
    }

    protected function getCookieValue()
    {
        // If we know for certain that the value isn't serialized, we don't need to go ahead and do this.
        if($this->looksLikeNoSerializedValue($this->value)) return $this->value;

        // this try catch is here to handle old cookies( before laravel 5.6.30 ) as they were serialized before.
        try{
            $this->value = unserialize($this->value);

            // At this point it is still possible we need to decode the value since the json could be serialized in the first place
            $this->value = $this->decodeCookieValue($this->value);

        }catch(\ErrorException $e){
            //
        }

        return $this->value == false ? null : $this->value;
    }

    protected function setCookieValue($value)
    {
        $this->value = $value;

        $this->cookieJar->queue(
            $this->cookieJar->make($this->cookieKey, $this->encodeCookieValue($this->value), $this->lifetime)
        );
    }

    public function forget(): void
    {
        $this->value = null;

        $this->cookieJar->queue(
            $this->cookieJar->forget($this->cookieKey)
        );
    }

    /**
     * Simple low-level check to see if value looks serialized or not.
     *
     * @param $value
     * @return bool
     */
    private function looksLikeNoSerializedValue($value): bool
    {
        if(!is_string($value)) return true;

        return (false === strpos($value, ';'));
    }

    private function encodeCookieValue($value)
    {
        return is_array($value) ? json_encode($value) : $value;
    }

    private function decodeCookieValue($cookieValue)
    {
        $value = $this->isJson($cookieValue) ? json_decode($cookieValue, true) : $cookieValue;

        if($value instanceof \stdClass) return (array) $value;

        return $value;
    }

    private function isJson($string){
        return (is_string($string) && is_array(json_decode($string, true)));
    }
}
