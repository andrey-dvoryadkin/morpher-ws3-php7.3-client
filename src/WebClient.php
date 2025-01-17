<?php
namespace Morpher\Ws3Client;

use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;

class WebClient
{
    private $tokenBase64;
    private $client;

    function __construct(string $url, string $token, float $timeout, $handler)
    {
        $this->tokenBase64 = base64_encode($token);

        $this->client = new \GuzzleHttp\Client([
            'base_url' => $url,
            'timeout' => $timeout,
            'handler' => $handler
        ]);    
    }

    public function getStandardHeaders(): array
    {
        $headers = ['Accept' => 'application/json'];

        if (!empty($this->tokenBase64))
        {
            $headers['Authorization'] = 'Basic '.$this->tokenBase64;
        }
        
        return $headers;
    }

    /**
     * @throws SystemError
     */
    public function send(
        string $Endpoint,
        $QueryParameters = [],
        string $Method = 'GET',
        $Headers = null,
        $body = null,
        $form_params = null): string
    {
        if ($Headers === null)
        {
            $Headers = $this->getStandardHeaders();
        }

        try
        {
            $response = $this->client->get($Endpoint, [
                'query' => $QueryParameters,
                'headers' => $Headers,
                'body' => $body,
                'form_params' => $form_params
            ]);

            $result = $response->getBody();

            return $result;
        }
        catch (ClientException $ex)
        {
            if (!$ex->hasResponse()) {
                throw new InvalidServerResponse("В ответе сервера нет тела.", "");
            }

            $response = $ex->getResponse();
            $responseBody = $response->getBody();
            $data = json_decode($responseBody, true);
            if (empty($data['code'])) {
                throw new InvalidServerResponse("В ответе сервера не найден параметр code.", $responseBody);
            }

            $msg = (string)($data['message'] ?? "Неизвестная ошибка");
            $errorCode = (int)($data['code']);

            if ($errorCode == 1) throw new RequestsDailyLimit($msg);
            if ($errorCode == 3) throw new IpBlocked($msg);
            if ($errorCode == 9) throw new TokenNotFound($msg);
            if ($errorCode == 10) throw new TokenIncorrectFormat($msg);

            throw new UnknownErrorCode($errorCode, $msg, $responseBody);
        }
        catch (ServerException $ex)
        {
            throw new ServerError($ex);
        }
        catch (GuzzleException $ex)
        {
            throw new ConnectionError($ex);
        }
    }

    /**
     * @throws InvalidServerResponse
     */
    public static function jsonDecode(string $text)
    {
        try
        {
            return json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        }
        catch (\JsonException $ex)
        {
            throw new InvalidServerResponse("Некорректный JSON ответ от сервера", $text);
        }
    }
}
