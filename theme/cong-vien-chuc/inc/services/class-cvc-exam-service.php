<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CVC_Exam_Service extends CVC_Api_Service {

	protected function endpoint(): string {
		return '/api/exams';
	}
}
