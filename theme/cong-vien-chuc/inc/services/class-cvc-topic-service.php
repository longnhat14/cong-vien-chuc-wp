<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CVC_Topic_Service extends CVC_Api_Service {

	protected function endpoint(): string {
		return '/api/topics';
	}
}
