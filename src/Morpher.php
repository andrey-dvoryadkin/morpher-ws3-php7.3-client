<?php
namespace Morpher\Ws3Client;

require_once __DIR__."/../vendor/autoload.php";
require_once("WebClient.php");
require_once("russian/Client.php");
require_once("qazaq/Client.php");

class Morpher
{
	public readonly Russian\Client $russian;
	public readonly Qazaq\Client $qazaq;
	
	private $_webClient;
	
	public function __construct(WebClient $webClient)
	{
		$this->_webClient=$webClient;
		$this->russian=new Russian\Client($webClient);
		$this->qazaq=new Qazaq\Client($webClient);
	}
}