<?php
/**
 * Homepage Next-Gen (Phase 11) - dữ liệu thật cho Live Pulse, Career
 * Roadmap, mini quiz. Public, không cần token.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CVC_Homepage_Service extends CVC_Api_Service {

	protected function endpoint(): string {
		return '/api/homepage';
	}

	public function pulse(): array {
		return $this->client->get( $this->endpoint() . '/pulse' );
	}

	public function careerRoadmap(): array {
		return $this->client->get( $this->endpoint() . '/career-roadmap' );
	}

	public function miniQuiz( int $count = 5 ): array {
		return $this->client->get( '/api/mini-quiz', array( 'count' => $count ) );
	}

	public function miniQuizCheck( string $quizToken, int $questionId, int $optionId ): array {
		return $this->client->post(
			'/api/mini-quiz/check',
			array(
				'quiz_token'  => $quizToken,
				'question_id' => $questionId,
				'option_id'   => $optionId,
			)
		);
	}
}
