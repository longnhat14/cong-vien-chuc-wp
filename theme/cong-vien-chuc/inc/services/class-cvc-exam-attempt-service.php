<?php
/**
 * Exam Attempt (Phase 10) - start/show/answer/submit/history. Đường dẫn
 * KHÔNG theo pattern /api/exam-attempts (base) đơn thuần - start() nằm
 * dưới /api/exams/{exam}/attempts, history() dưới /api/my-exam-attempts -
 * nên class này KHÔNG kế thừa list()/find() mặc định của CVC_Api_Service.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CVC_Exam_Attempt_Service extends CVC_Api_Service {

	protected function endpoint(): string {
		return '/api/exam-attempts';
	}

	public function start( int $examId, string $mode, string $token ): array {
		return $this->client->post( '/api/exams/' . $examId . '/attempts', array( 'mode' => $mode ), $token );
	}

	public function show( int $attemptId, string $token ): array {
		return $this->client->get( $this->endpoint() . '/' . $attemptId, array(), $token );
	}

	/**
	 * @param array<string, mixed> $payload question_id + question_option_id|option_ids|answer_value
	 */
	public function answer( int $attemptId, array $payload, string $token ): array {
		return $this->client->put( $this->endpoint() . '/' . $attemptId . '/answer', $payload, $token );
	}

	public function submit( int $attemptId, string $token ): array {
		return $this->client->post( $this->endpoint() . '/' . $attemptId . '/submit', array(), $token );
	}

	/**
	 * @param array<string, mixed> $query
	 */
	public function history( array $query, string $token ): array {
		return $this->client->get( '/api/my-exam-attempts', $query, $token );
	}
}
