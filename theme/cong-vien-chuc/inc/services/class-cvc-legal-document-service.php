<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CVC_Legal_Document_Service extends CVC_Api_Service {

	protected function endpoint(): string {
		return '/api/legal-documents';
	}
}
