<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Connector\Sabre\RequestTest;

use Sabre\HTTP\Request;
use Sabre\HTTP\Response;

class Sapi {
	/**
	 * @var \Sabre\HTTP\Request
	 */
	private $request;

	/**
	 * @var \Sabre\HTTP\Response
	 */
	private $response;

	/**
	 * This static method will create a new Request object, based on the
	 * current PHP request.
	 *
	 * @return \Sabre\HTTP\Request
	 */
	public function getRequest() {
		return $this->request;
	}

	public function __construct(Request $request) {
		$this->request = $request;
	}

	/**
	 * @param \Sabre\HTTP\Response $response
	 * @return void
	 */
	public function sendResponse(Response $response) {
		$this->response = $response;
	}

	/**
	 * @return \Sabre\HTTP\Response
	 */
	public function getResponse() {
		return $this->response;
	}
}
