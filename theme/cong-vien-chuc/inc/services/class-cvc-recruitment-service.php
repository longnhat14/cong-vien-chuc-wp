<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CVC_Recruitment_Service extends CVC_Api_Service {

	protected function endpoint(): string {
		return '/api/recruitments';
	}
}
