<?php

use IlluminateAgnostic\Arr\Support\Arr;
use Kubio\Core\Utils;
use Kubio\FileLog;

function kubio_ai_get_logs() {
	$logs_file        = FileLog::get_log_files( 'AI' );
	$logs             = array();
	$total_usage      = 0;
	$total_credits    = 0;
	$error_usage      = 0;
	$sections         = 0;
	$sections_credits = 0;

	$lines = array();

	$tpc = Arr::get( $_REQUEST, 'tpc', 25 );
	$tpc = intval( $tpc ) ? intval( $tpc ) : 25;

	foreach ( $logs_file as $log_file ) {
		$lines = array_merge( $lines, explode( "\n", file_get_contents( $log_file ) ) );
	}

	foreach ( $lines as $line ) {
		$log = json_decode( $line, true );

		if ( ! is_array( $log ) ) {
			continue;
		}

		$code = Arr::get( $log, 'data.code', 'Unknown code' );

		$completion   = Arr::get( $log, 'data.completion', array() );
		$completion   = Utils::maybeJSONDecode( $completion );
		$type         = Arr::get( $log, 'type', 'unknown' );
		$total_tokens = Arr::get( $log, 'data.usage.total_tokens', 0 );

		$total_usage += $total_tokens;
		$credits      = $total_tokens / $tpc;
		$credits      = floor( $credits ) + 0.5 > $credits ? floor( $credits ) : ceil( $credits );

		if ( $type === 'error' ) {

			$completion   = is_array( $completion ) ? Utils::humanizeArray( $completion, '  ' ) : $completion;
			$error_usage += $total_tokens;
		} else {
			$total_credits += $credits;

			if ( strpos( $code, '/generate-page-section' ) !== false || strpos( $code, '/rephrase-page-section' ) !== false ) {
				$sections++;
				$sections_credits += $credits;
			}
		}

		$normalized_logs = array(
			'start_time' => Arr::get( $log, 'data.start_time', 0 ),
			'call_id'    => Arr::get( $log, 'data.call_id', null ),
			'type'       => $type,
			'ts'         => Arr::get( $log, 'date', 0 ),
			'date'       => gmdate(
				'Y-m-d H:i:s',
				Arr::get( $log, 'date', 0 )
			),
			'code'       => $code,
			'prompts'    => Arr::get( $log, 'data.prompts', array() ),
			'completion' => is_string( $completion ) ? $completion : json_encode( $completion, JSON_PRETTY_PRINT ),
			'usage'      => array(
				'prompt'     => Arr::get( $log, 'data.usage.prompt_tokens', 0 ),
				'completion' => Arr::get( $log, 'data.usage.completion_tokens', 0 ),
				'total'      => $total_tokens,
				'credits'    => $type === 'error' ? 0 : $credits,
			),
		);

		$logs[] = $normalized_logs;

	}

	usort(
		$logs,
		function( $a, $b ) {
			return $a['start_time'] - $b['start_time'];
		}
	);

	$section_avg_credits = $sections ? round( $sections_credits / $sections, 1 ) : 0;

	return  array(
		'logs'                => $logs,
		'total_usage'         => $total_usage,
		'error_usage'         => $error_usage,
		'total_credits'       => $total_credits,
		'tpc'                 => $tpc,
		'sections'            => $sections,
		'sections_credits'    => $sections_credits,
		'section_avg_credits' => $section_avg_credits,
	);
}

?>
<script>
	let kubioAILogs = [];
	function kubioAILogDownload(index){

		const log = kubioAILogs[index];

		const promptsArray = log.prompts.map(p=>{
			return [
				`### Role: ${p.role.toUpperCase()}`,
				"\n",
				"```",
				p.content.replaceAll("```","``"),
				"```",
			].join("\n")
		})

		const content = [
			`# Kubio AI Log`,
			`Log type: ${log.type}`,
			`Log code: ${log.code}`,
			`\n`,
			`Site: ${window.location}`,
			`\n\n`,
			`## PROMPTS`,
			...promptsArray,
			`\n\n\n`,
			`## COMPLETION`,
			"```",
			log.completion,
			"```",
			`\n\n\n`,
			`## USAGE`,
			`\n`,
			"```",
			JSON.stringify(log.usage || {},null,2),
			"```",
		].join("\n");


		const file = new File([content], 'log.md', {
			type: 'text/plain',
		});

		const link = document.createElement('a')
		const url = URL.createObjectURL(file)

		link.href = url
		link.download = file.name
		document.body.appendChild(link)
		link.click()

		document.body.removeChild(link)
		window.URL.revokeObjectURL(url)
	}
</script>
<div class="tab-page">
	<div class="kubio-logs-main-container">
	<div class="kubio-admin-page-section kubio-ai-logs-section">
			<div class="kubio-admin-page-section-content kubio-ai-logs">
				<div class="kubio-ai-section-title">
				<?php $kubio_ai_logs_data = kubio_ai_get_logs(); ?>
				<h2>
					Kubio AI Logs
					<span>
						<?php
						printf(
							'Tokens: %1$s ( %2$s OK | %3$s ERR )',
							$kubio_ai_logs_data['total_usage'],
							$kubio_ai_logs_data['total_usage'] - $kubio_ai_logs_data['error_usage'],
							$kubio_ai_logs_data['error_usage']
						);
						?>
					</span>
					<span>
						<?php
						printf(
							'%s credits ( %s for sections ) | %s sections | ~%s credits/section',
							$kubio_ai_logs_data['total_credits'],
							$kubio_ai_logs_data['sections_credits'],
							$kubio_ai_logs_data['sections'],
							$kubio_ai_logs_data['section_avg_credits']
						);
						?>
					</span>
				
				</h2>
				<div class="align-items-end d-flex flex-column" style="gap:8px">
					<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'action', 'kubio_clear_ai_logs', admin_url( 'admin-ajax.php' ) ), 'kubio_clear_ai_logs' ) ); ?>" class="button-link-delete"><?php echo 'Clear logs'; ?></a>
					<form method="get">
						<input name="page" value="kubio-get-started" type="hidden"/>
						<input name="tab" value="ai-logs" type="hidden"/>
						<label>
							Tokens/Credit
							<input name="tpc" value="<?php echo esc_attr( $kubio_ai_logs_data['tpc'] ); ?>">
						</label>
						<button>Set</button>
					</form>
				</div>
				</div>
				<div class="kubio-ai-logs-wrapper">
					<?php foreach ( $kubio_ai_logs_data['logs']  as $log_index => $log ) : ?>
					<details>
						<script>
							kubioAILogs.push(<?php echo json_encode( $log ); ?>);
						</script>
						<summary>
								<span class="kubio-ai-log-type kubio-ai-log-type--<?php echo esc_attr( $log['type'] ); ?>"><?php echo $log['type']; ?></span>
								<span class="kubio-ai-log-code">
									<?php echo $log['code']; ?>
								</span>
								<div class="kubio-ai-log-tags">
									<?php if ( Arr::get( $log, 'usage.total', 0 ) && $log['type'] !== 'error' ) : ?>
										<span class="kubio-ai-log-tokens"><?php echo Arr::get( $log, 'usage.credits', 0 ); ?> credits ( <?php echo Arr::get( $log, 'usage.total', 0 ); ?> tokens )</span>
									<?php endif; ?>
									<?php printf( $log['call_id'] ? '<span class="kubio-ai-log-call-id">%s</span>' : '', $log['call_id'] ); ?>
									<span class="kubio-ai-log-date"><?php echo $log['date']; ?></span>
									<button onclick="kubioAILogDownload('<?php echo esc_attr( $log_index ); ?>')" class="button button-primary">
										<span class="dashicons dashicons-download " style="line-height: 30px;"></span>
									</button>
								</div>
						</summary>
						<div>
							<div class="kubio-ai-log-prompts">
								<?php foreach ( $log['prompts'] as $prompt ) : ?>
									<details class="kubio-ai-log-prompt">
										<summary class="kubio-ai-log-role">
											<span><?php echo Arr::get( $prompt, 'role', 'Unknown role' ); ?></span>
										</summary>
										<div class="kubio-ai-log-content">
											<?php
											$prompt_content = Arr::get( $prompt, 'content', 'Unknown content' );

											if ( ! is_string( $prompt_content ) && ! is_numeric( $prompt_content ) ) {
												$prompt_content = json_encode( $prompt_content, JSON_PRETTY_PRINT );
											}

											printf( '<pre>%s</pre>', esc_html( $prompt_content ) );
											?>

										</div>
									</details>
								<?php endforeach; ?>
							</div>
							<div class="kubio-ai-log-response">
								<pre><?php echo esc_html( $log['completion'] ); ?></pre>
							</div>
							<div class="kubio-ai-log-usage">
								<span class="kubio-ai-usage-prompt"><?php printf( 'Prompt tokens: %s', Arr::get( $log, 'usage.prompt', 0 ) ); ?></span>
								<span class="kubio-ai-usage-completion"><?php printf( 'Completion tokens: %s', Arr::get( $log, 'usage.completion', 0 ) ); ?></span>
								<span class="kubio-ai-usage-total"><?php printf( 'Total tokens: %s', Arr::get( $log, 'usage.total', 0 ) ); ?></span>
								<span class="kubio-ai-usage-total"><?php printf( 'Credits: %s', Arr::get( $log, 'usage.credits', 0 ) ); ?></span>
							</div>
						</div>
					</details>
					<?php endforeach; ?>
			</div>
			</div>

		</div>
	</div>
</div>
