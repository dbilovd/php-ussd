<?php

namespace Dbilovd\PHUSSD\Pages;

use Dbilovd\PHUSSD\Contracts\Requests;
use Dbilovd\PHUSSD\Contracts\Pages;
use Illuminate\Support\Facades\Redis;

abstract class BasePage implements Pages
{
	/**
	 * Request object
	 * 
	 * @var \Dbilovd\PHUSSD\Requests
	 */
	public $request;

	/**
	 * Message string to return
	 * 
	 * @var String
	 */
	public $message = "Default Message";

	/**
	 * Next page class name
	 * 
	 * @var String
	 */
	public $nextPage = false;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct($request)
	{
		$this->request = $request;
	}

	/**
	 * Returns a list of sub menus
	 *
	 * @return Array Child menus
	 */
	public function menus()
	{
		return $this->menus ?: false;
	}

	/**
	 * Check if User Response is valid
	 * 	
	 * @return Boolean Response content
	 */
	public function validUserResponse ($userResponse)
	{
		if (property_exists($this, 'menus') && is_array($this->menus)) {
			return array_key_exists($userResponse, $this->menus);
		}

		return true;
	}

	/**
	 * Return an instance of the next child class depending on the user input
	 *
	 * @param String $userResponse 
	 * @return \Dbilovd\PHUSSD\Contracts\Pages
	 */
	public function next ($userResponse)
	{
		return $this->nextPage ?: false;
	}

	/**
	 * Prepare user response for storing
	 *
	 * @return mixed Prepared user response for storing in DB
	 */
	public function preparedUserResponse($userResponse)
	{
		return $userResponse;
	}

	/**
	 * Save the Issue Title
	 *
	 * @param String $userResponse The user's response to being presented this page
	 * @return Boolean
	 */
	public function save ($userResponse, $sessionId)
	{
		$preparedUserResponse = $this->preparedUserResponse($userResponse);
		
		if (property_exists($this, 'dataFieldKey') && $preparedUserResponse) {
			$existingData = json_decode("{}");
			if (Redis::hExists($sessionId, "data")) {
				$existingData = json_decode(
					Redis::hGet($sessionId, "data")
				);
			}

			$existingData->{$this->dataFieldKey} = $preparedUserResponse;

			Redis::hSet($sessionId, "data", json_encode($existingData));

			if (method_exists($this, 'fireEvents')) {
				$this->fireEvents($sessionId);
			}
		}
		
		return true;
	}

	/**
	 * Return the response type for this particular page per the request
	 * 
	 * @return mixed
	 */
	public function responseType ()
	{
		return $this->request->getResponseType(
			$this->responseType ?: 'end'
		);
	}
	
	/**
	 * Send response
	 *
	 * @return String Response content
	 */
	public function response()
	{
		return $this->request->response($this);
	}

	/**
	 * Return message to send back to client
	 * 	
	 * @return String Response content
	 */
	public function message()
	{
		return $this->message;
	}
}