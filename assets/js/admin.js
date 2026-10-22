/**
 * DPLS Schema AI - Admin Dashboard & Editor Interactivity
 *
 * @package DPLS\SchemaAI
 * @author  Dev Pattern Labs
 */

(function($) {
	'use strict';

	var DplsAdmin = {
		isScanning: false,

		init: function() {
			this.bindEvents();
		},

		bindEvents: function() {
			var self = this;

			// Content Table Actions
			$(document).on('click', '.dpls-action-analyze', function(e) {
				e.preventDefault();
				self.handleAnalyze($(this));
			});

			$(document).on('click', '.dpls-action-generate', function(e) {
				e.preventDefault();
				self.handleGenerate($(this));
			});

			$(document).on('click', '.dpls-action-preview', function(e) {
				e.preventDefault();
				self.handlePreview($(this));
			});

			$(document).on('click', '.dpls-action-apply', function(e) {
				e.preventDefault();
				self.handleApply($(this));
			});

			$(document).on('click', '.dpls-action-validate', function(e) {
				e.preventDefault();
				self.handleValidate($(this));
			});

			$(document).on('click', '.dpls-action-disable', function(e) {
				e.preventDefault();
				self.handleDisable($(this));
			});

			$(document).on('click', '.dpls-action-history', function(e) {
				e.preventDefault();
				self.handleHistory($(this));
			});

			// Resumable Background Batch Scan
			$(document).on('click', '.dpls-trigger-batch-scan, .dpls-trigger-bulk-scan', function(e) {
				e.preventDefault();
				self.handleBatchScan($(this));
			});

			// Clear History
			$(document).on('click', '.dpls-clear-all-history', function(e) {
				e.preventDefault();
				self.handleClearHistory($(this));
			});

			// Modals Close
			$(document).on('click', '.dpls-modal-close, .dpls-modal-close-btn', function() {
				$('.dpls-modal-backdrop').fadeOut(150);
			});

			$(document).on('click', '.dpls-modal-backdrop', function(e) {
				if ($(e.target).hasClass('dpls-modal-backdrop')) {
					$('.dpls-modal-backdrop').fadeOut(150);
				}
			});

			$(document).keyup(function(e) {
				if (e.key === 'Escape') {
					$('.dpls-modal-backdrop').fadeOut(150);
				}
			});

			// Copy JSON to clipboard
			$(document).on('click', '.dpls-copy-json', function() {
				var targetArea = $(this).closest('.dpls-card, .dpls-modal').find('.dpls-code-editor');
				if (targetArea.length) {
					targetArea.select();
					navigator.clipboard.writeText(targetArea.val()).then(function() {
						self.showNotice(dplsSchemaAi.i18n.copied, 'success');
					});
				}
			});

			// Apply from modal with explicit confirmation
			$(document).on('click', '.dpls-modal-apply-btn', function(e) {
				e.preventDefault();
				if (!confirm('Are you sure you want to publish this schema to your live site?')) {
					return;
				}
				var postId = $('#dpls-modal-post-id').val();
				var customJson = $('#dpls-modal-schema-code').val();
				self.applySchema(postId, customJson, $(this));
			});

			// Modal schema type regenerate
			$(document).on('click', '#dpls-modal-regenerate-btn', function(e) {
				e.preventDefault();
				var postId = $('#dpls-modal-post-id').val();
				var schemaType = $('#dpls-modal-change-type').val();
				if (!postId) return;

				var $btn = $(this);
				$btn.prop('disabled', true).text(dplsSchemaAi.i18n.generating);

				$.post(dplsSchemaAi.ajaxUrl, {
					action: 'dpls_generate_schema',
					nonce: dplsSchemaAi.nonce,
					post_id: postId,
					schema_type: schemaType
				}, function(res) {
					$btn.prop('disabled', false).text('Regenerate');
					if (res.success && res.data) {
						$('#dpls-modal-schema-code').val(res.data.schema_json);
						self.renderMissingInfo(res.data.missing_recommended || [], res.data.warnings || []);
						self.showNotice('Proposal regenerated for ' + res.data.schema_type, 'success');
					} else {
						self.showNotice(res.data ? res.data.message : dplsSchemaAi.i18n.error, 'error');
					}
				});
			});

			// Rollback to previous version in modal
			$(document).on('click', '#dpls-modal-rollback-btn', function(e) {
				e.preventDefault();
				var postId = $('#dpls-modal-post-id').val();
				var targetVer = $('#dpls-modal-rollback-version').val();
				if (!postId || !targetVer) return;

				if (!confirm('Rollback schema to version ' + targetVer + '?')) {
					return;
				}

				var $btn = $(this);
				$btn.prop('disabled', true);

				$.post(dplsSchemaAi.ajaxUrl, {
					action: 'dpls_rollback_schema',
					nonce: dplsSchemaAi.nonce,
					post_id: postId,
					version: targetVer
				}, function(res) {
					$btn.prop('disabled', false);
					if (res.success && res.data) {
						var row = $('#dpls-post-row-' + postId);
						if (row.length) {
							row.find('.column-schema-status').html(res.data.status_badge);
						}
						self.showNotice(res.data.message, 'success');
						$('#dpls-preview-modal').fadeOut(150);
					} else {
						self.showNotice(res.data ? res.data.message : dplsSchemaAi.i18n.error, 'error');
					}
				});
			});

			// Generator page controls
			$('#dpls-generator-post-select').on('change', function() {
				var val = $(this).val();
				$('#dpls-generator-btn').prop('disabled', !val);
				$('#dpls-btn-ai-analyze').prop('disabled', !val);
				$('#dpls-ai-analysis-card').hide();
			});

			$('#dpls-btn-ai-analyze').on('click', function(e) {
				e.preventDefault();
				var postId = $('#dpls-generator-post-select').val();
				if (!postId) return;

				var $btn = $(this);
				$btn.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> Analyzing with AI...');

				$.post(dplsSchemaAi.ajaxUrl, {
					action: 'dpls_ai_analyze_post',
					nonce: dplsSchemaAi.nonce,
					post_id: postId
				}, function(res) {
					$btn.prop('disabled', false).html('<span class="dashicons dashicons-lightbulb"></span> AI Content Analysis & Comparison');

					if (res.success && res.data && res.data.analysis) {
						var an = res.data.analysis;
						var html = '';

						if (an.fallback || !an.success) {
							html += '<div style="color:#d63638; font-weight:600; margin-bottom:8px;">' + (an.message || 'AI is disabled or offline. Deterministic rule engine active.') + '</div>';
							if (an.rule_result) {
								html += '<div><strong>Deterministic Rule Prediction:</strong> ' + an.rule_result.type + ' (' + Math.round((an.rule_result.confidence || 0.8) * 100) + '% confidence)</div>';
								if (an.rule_result.reasons && an.rule_result.reasons.length) {
									html += '<ul style="margin:4px 0 0 16px; list-style:disc;">';
									for (var i = 0; i < an.rule_result.reasons.length; i++) {
										html += '<li>' + an.rule_result.reasons[i] + '</li>';
									}
									html += '</ul>';
								}
							}
						} else {
							html += '<div style="display:flex; justify-content:space-between; margin-bottom:8px;">';
							html += '<span><strong>AI Suggested Type:</strong> <span class="dpls-badge dpls-badge-applied">' + an.suggested_type + '</span> (' + Math.round((an.confidence || 0.7) * 100) + '% confidence)</span>';
							if (an.rule_comparison) {
								var matchBadge = an.rule_comparison.agrees ? '<span style="color:#00a32a;">✓ Agrees with rules</span>' : '<span style="color:#dba617;">⚠ Diverges from rules (' + an.rule_comparison.rule_type + ')</span>';
								html += '<span>' + matchBadge + '</span>';
							}
							html += '</div>';

							if (an.reasons && an.reasons.length) {
								html += '<div><strong>Identified Factors:</strong><ul style="margin:4px 0 8px 16px; list-style:disc;">';
								for (var r = 0; r < an.reasons.length; r++) {
									html += '<li>' + an.reasons[r] + '</li>';
								}
								html += '</ul></div>';
							}

							if (an.warnings && an.warnings.length) {
								html += '<div style="color:#dba617; font-size:12px; margin-bottom:8px;"><strong>Warnings:</strong> ' + an.warnings.join('; ') + '</div>';
							}

							html += '<button type="button" class="button button-small button-primary dpls-adopt-ai-type" data-type="' + an.suggested_type + '">Adopt ' + an.suggested_type + ' in Generator</button>';
						}

						$('#dpls-ai-analysis-content').html(html);
						$('#dpls-ai-analysis-card').slideDown(200);
					} else {
						self.showNotice(res.data ? res.data.message : dplsSchemaAi.i18n.error, 'error');
					}
				});
			});

			$(document).on('click', '.dpls-adopt-ai-type', function(e) {
				e.preventDefault();
				var adoptedType = $(this).data('type');
				if (adoptedType) {
					$('#dpls-generator-type-select').val(adoptedType);
					self.showNotice('Set target schema type to ' + adoptedType, 'info');
				}
			});

			$(document).on('click', '#dpls-btn-test-ai', function(e) {
				e.preventDefault();
				var $btn = $(this);
				var $status = $('#dpls-ai-test-status');
				var $spinner = $('#dpls-ai-test-spinner');

				$btn.prop('disabled', true);
				$spinner.show();
				$status.html('<span style="color:#50575e;">Connecting to Ollama endpoint...</span>');

				$.post(dplsSchemaAi.ajaxUrl, {
					action: 'dpls_test_ai_connection',
					nonce: dplsSchemaAi.nonce
				}, function(res) {
					$btn.prop('disabled', false);
					$spinner.hide();

					if (res.success && res.data) {
						var data = res.data;
						var modelCount = (data.available_models && data.available_models.length) ? data.available_models.length : 0;
						var info = '✓ Connected (' + (data.latency_ms || 0) + 'ms). ' + modelCount + ' local models found.';
						$status.html('<span style="color:#00a32a;">' + info + '</span>');
					} else {
						var msg = res.data ? res.data.message : 'Connection failed.';
						$status.html('<span style="color:#d63638;">✗ ' + msg + '</span>');
					}
				});
			});

			$('#dpls-generator-btn').on('click', function(e) {
				e.preventDefault();
				var postId = $('#dpls-generator-post-select').val();
				var schemaType = $('#dpls-generator-type-select').val();
				if (!postId) return;

				var $btn = $(this);
				$btn.prop('disabled', true).text(dplsSchemaAi.i18n.generating);

				$.post(dplsSchemaAi.ajaxUrl, {
					action: 'dpls_generate_schema',
					nonce: dplsSchemaAi.nonce,
					post_id: postId,
					schema_type: schemaType
				}, function(res) {
					$btn.prop('disabled', false).html('<span class="dashicons dashicons-admin-generic"></span> ' + 'Generate JSON-LD');
					if (res.success && res.data) {
						$('#dpls-generator-output').val(res.data.schema_json);
						$('#dpls-generator-apply-btn').prop('disabled', false);
						$('#dpls-generator-validate-btn').prop('disabled', false);
						self.showNotice(res.data.message || dplsSchemaAi.i18n.success, 'success');
					} else {
						self.showNotice(res.data ? res.data.message : dplsSchemaAi.i18n.error, 'error');
					}
				});
			});

			$('#dpls-generator-apply-btn').on('click', function(e) {
				e.preventDefault();
				if (!confirm('Are you sure you want to apply this schema to the selected content?')) {
					return;
				}
				var postId = $('#dpls-generator-post-select').val();
				var schemaJson = $('#dpls-generator-output').val();
				if (!postId || !schemaJson) return;
				self.applySchema(postId, schemaJson, $(this));
			});

			$('#dpls-generator-validate-btn').on('click', function(e) {
				e.preventDefault();
				var postId = $('#dpls-generator-post-select').val();
				if (!postId) return;
				self.validateSchema(postId, $(this));
			});
		},

		showNotice: function(message, type) {
			type = type || 'info';
			var $notice = $('#dpls-ajax-notice');
			if (!$notice.length) {
				$notice = $('<div id="dpls-ajax-notice" class="notice is-dismissible" style="display:none;"><p id="dpls-ajax-notice-message"></p></div>');
				$('.dpls-admin-wrap').prepend($notice);
			}

			$notice.removeClass('notice-info notice-success notice-error notice-warning')
				.addClass('notice-' + type);
			$('#dpls-ajax-notice-message').text(message);
			$notice.fadeIn(200);

			setTimeout(function() {
				$notice.fadeOut(300);
			}, 5000);
		},

		renderMissingInfo: function(missing, warnings) {
			var $wrap = $('#dpls-modal-missing-info');
			var $list = $('#dpls-modal-missing-list');
			$list.empty();

			var items = (missing || []).concat(warnings || []);
			if (items.length) {
				items.forEach(function(item) {
					$list.append($('<li>').text(item));
				});
				$wrap.show();
			} else {
				$wrap.hide();
			}
		},

		handleAnalyze: function($btn) {
			var self = this;
			var postId = $btn.data('post-id');
			var origHtml = $btn.html();

			$btn.prop('disabled', true).text(dplsSchemaAi.i18n.analyzing);

			$.post(dplsSchemaAi.ajaxUrl, {
				action: 'dpls_analyze_post',
				nonce: dplsSchemaAi.nonce,
				post_id: postId,
				force: 1
			}, function(res) {
				$btn.prop('disabled', false).html(origHtml);
				if (res.success && res.data) {
					var row = $('#dpls-post-row-' + postId);
					row.find('.column-suggested').html('<code class="dpls-code-type">' + res.data.suggested_type + '</code>');
					row.find('.column-schema-status').html(res.data.status_badge);
					row.find('.column-last-analyzed').text(res.data.last_analyzed);
					row.find('.dpls-action-preview, .dpls-action-apply, .dpls-action-validate').prop('disabled', false);

					self.showNotice(dplsSchemaAi.i18n.success, 'success');
				} else {
					self.showNotice(res.data ? res.data.message : dplsSchemaAi.i18n.error, 'error');
				}
			});
		},

		handleGenerate: function($btn) {
			var self = this;
			var postId = $btn.data('post-id');
			var origHtml = $btn.html();

			$btn.prop('disabled', true).text(dplsSchemaAi.i18n.generating);

			$.post(dplsSchemaAi.ajaxUrl, {
				action: 'dpls_generate_schema',
				nonce: dplsSchemaAi.nonce,
				post_id: postId
			}, function(res) {
				$btn.prop('disabled', false).html(origHtml);
				if (res.success && res.data) {
					var row = $('#dpls-post-row-' + postId);
					row.find('.column-schema-status').html(res.data.status_badge);
					row.find('.dpls-action-preview, .dpls-action-apply, .dpls-action-validate').prop('disabled', false);

					self.showNotice(res.data.message || dplsSchemaAi.i18n.success, 'success');
				} else {
					self.showNotice(res.data ? res.data.message : dplsSchemaAi.i18n.error, 'error');
				}
			});
		},

		handlePreview: function($btn) {
			var self = this;
			var postId = $btn.data('post-id');

			$.post(dplsSchemaAi.ajaxUrl, {
				action: 'dpls_preview_schema',
				nonce: dplsSchemaAi.nonce,
				post_id: postId
			}, function(res) {
				if (res.success && res.data) {
					$('#dpls-modal-post-id').val(res.data.post_id);
					$('#dpls-modal-post-title').text(res.data.post_title);
					$('#dpls-modal-status-badge').html(res.data.status_badge);
					$('#dpls-modal-schema-code').val(res.data.schema_json);

					self.renderMissingInfo(res.data.missing_recommended, res.data.warnings);

					var valBox = $('#dpls-modal-validation-summary');
					valBox.empty();

					if (res.data.validation) {
						if (res.data.validation.is_valid && (!res.data.validation.warnings || !res.data.validation.warnings.length)) {
							valBox.removeClass('invalid').addClass('valid').text('Schema is syntactically valid and complies with Schema.org standards.').show();
						} else if (!res.data.validation.is_valid) {
							var errHtml = '<strong>Errors:</strong> ' + res.data.validation.errors.join('; ');
							valBox.removeClass('valid').addClass('invalid').html(errHtml).show();
						} else {
							var warnHtml = '<strong>Warnings:</strong> ' + res.data.validation.warnings.join('; ');
							valBox.removeClass('valid').addClass('invalid').html(warnHtml).show();
						}
					}

					// Fetch rollback versions
					$.post(dplsSchemaAi.ajaxUrl, {
						action: 'dpls_view_history',
						nonce: dplsSchemaAi.nonce,
						post_id: postId
					}, function(hRes) {
						var $rbWrap = $('#dpls-modal-rollback-wrap');
						var $select = $('#dpls-modal-rollback-version');
						$select.empty();

						if (hRes.success && hRes.data.versions && hRes.data.versions.length) {
							hRes.data.versions.forEach(function(v) {
								$select.append($('<option>').val(v.version).text('Version ' + v.version + ' (' + v.date + ')'));
							});
							$rbWrap.show();
						} else {
							$rbWrap.hide();
						}
					});

					$('#dpls-preview-modal').fadeIn(150);
				} else {
					self.showNotice(res.data ? res.data.message : dplsSchemaAi.i18n.error, 'error');
				}
			});
		},

		handleApply: function($btn) {
			var postId = $btn.data('post-id');
			if (!confirm('Are you sure you want to apply this structured data schema to your live site?')) {
				return;
			}
			this.applySchema(postId, '', $btn);
		},

		applySchema: function(postId, customJson, $btn) {
			var self = this;
			var origHtml = $btn ? $btn.html() : '';
			if ($btn) $btn.prop('disabled', true).text(dplsSchemaAi.i18n.applying);

			$.post(dplsSchemaAi.ajaxUrl, {
				action: 'dpls_apply_schema',
				nonce: dplsSchemaAi.nonce,
				post_id: postId,
				schema_json: customJson
			}, function(res) {
				if ($btn) $btn.prop('disabled', false).html(origHtml);
				if (res.success && res.data) {
					var row = $('#dpls-post-row-' + postId);
					if (row.length) {
						row.find('.column-schema-status').html(res.data.status_badge);
						row.find('.dpls-action-disable').prop('disabled', false);
					}
					$('#dpls-modal-status-badge').html(res.data.status_badge);
					self.showNotice(res.data.message || dplsSchemaAi.i18n.success, 'success');
					$('#dpls-preview-modal').fadeOut(150);
				} else {
					self.showNotice(res.data ? res.data.message : dplsSchemaAi.i18n.error, 'error');
				}
			});
		},

		handleValidate: function($btn) {
			var postId = $btn.data('post-id');
			this.validateSchema(postId, $btn);
		},

		validateSchema: function(postId, $btn) {
			var self = this;
			var origHtml = $btn ? $btn.html() : '';
			if ($btn) $btn.prop('disabled', true).text(dplsSchemaAi.i18n.validating);

			$.post(dplsSchemaAi.ajaxUrl, {
				action: 'dpls_validate_schema',
				nonce: dplsSchemaAi.nonce,
				post_id: postId
			}, function(res) {
				if ($btn) $btn.prop('disabled', false).html(origHtml);
				if (res.success && res.data) {
					var msg = res.data.is_valid ? 'Schema is valid!' : 'Schema has errors!';
					if (res.data.warnings && res.data.warnings.length) {
						msg += ' (Warnings: ' + res.data.warnings.join(', ') + ')';
					}
					var row = $('#dpls-post-row-' + postId);
					if (row.length && res.data.status_badge) {
						row.find('.column-schema-status').html(res.data.status_badge);
					}
					self.showNotice(msg, res.data.is_valid ? 'success' : 'warning');
				} else {
					self.showNotice(res.data ? res.data.message : dplsSchemaAi.i18n.error, 'error');
				}
			});
		},

		handleDisable: function($btn) {
			var self = this;
			if (!confirm(dplsSchemaAi.i18n.confirmDisable)) {
				return;
			}

			var postId = $btn.data('post-id');
			var origHtml = $btn.html();
			$btn.prop('disabled', true);

			$.post(dplsSchemaAi.ajaxUrl, {
				action: 'dpls_disable_schema',
				nonce: dplsSchemaAi.nonce,
				post_id: postId
			}, function(res) {
				$btn.prop('disabled', false).html(origHtml);
				if (res.success && res.data) {
					var row = $('#dpls-post-row-' + postId);
					row.find('.column-schema-status').html(res.data.status_badge);
					$btn.prop('disabled', true);
					self.showNotice(res.data.message || dplsSchemaAi.i18n.success, 'success');
				} else {
					self.showNotice(res.data ? res.data.message : dplsSchemaAi.i18n.error, 'error');
				}
			});
		},

		handleHistory: function($btn) {
			var self = this;
			var postId = $btn.data('post-id');

			$.post(dplsSchemaAi.ajaxUrl, {
				action: 'dpls_view_history',
				nonce: dplsSchemaAi.nonce,
				post_id: postId
			}, function(res) {
				if (res.success && res.data) {
					var tbody = $('#dpls-history-list');
					tbody.empty();

					if (!res.data.events || !res.data.events.length) {
						tbody.append('<tr><td colspan="4">No history records found for this item.</td></tr>');
					} else {
						res.data.events.forEach(function(item) {
							var rowHtml = '<tr>' +
								'<td>' + $('<div>').text(item.date).html() + '</td>' +
								'<td><code class="dpls-code-type">' + $('<div>').text(item.action).html() + '</code></td>' +
								'<td>' + $('<div>').text(item.user).html() + '</td>' +
								'<td>' + $('<div>').text(item.message).html() + '</td>' +
								'</tr>';
							tbody.append(rowHtml);
						});
					}

					$('#dpls-history-modal').fadeIn(150);
				} else {
					self.showNotice(res.data ? res.data.message : dplsSchemaAi.i18n.error, 'error');
				}
			});
		},

		handleBatchScan: function($btn) {
			var self = this;
			if (self.isScanning) return;

			if (!confirm(dplsSchemaAi.i18n.confirmScan)) {
				return;
			}

			self.isScanning = true;
			$btn.prop('disabled', true).text(dplsSchemaAi.i18n.analyzing);

			var $wrap = $('#dpls-scan-progress-wrap');
			var $bar = $('#dpls-scan-progress-bar');
			var $percent = $('#dpls-scan-progress-percent');
			var $label = $('#dpls-scan-progress-label');

			$wrap.slideDown(200);
			$bar.css('width', '0%');
			$percent.text('0%');

			// 1. Start job
			$.post(dplsSchemaAi.ajaxUrl, {
				action: 'dpls_start_scan_job',
				nonce: dplsSchemaAi.nonce,
				batch_size: 15
			}, function(res) {
				if (res.success) {
					self.runBatchChunk($btn);
				} else {
					self.isScanning = false;
					$btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Scan All Content');
					self.showNotice(res.data ? res.data.message : dplsSchemaAi.i18n.error, 'error');
				}
			});
		},

		runBatchChunk: function($btn) {
			var self = this;
			var $bar = $('#dpls-scan-progress-bar');
			var $percent = $('#dpls-scan-progress-percent');
			var $label = $('#dpls-scan-progress-label');

			$.post(dplsSchemaAi.ajaxUrl, {
				action: 'dpls_process_scan_batch',
				nonce: dplsSchemaAi.nonce,
				batch_size: 15
			}, function(res) {
				if (res.success && res.data) {
					var p = res.data.percent || 0;
					$bar.css('width', p + '%');
					$percent.text(p + '%');
					$label.html('<strong>Scanned ' + res.data.scanned + ' of ' + res.data.total + ' items...</strong>');

					if (res.data.completed) {
						self.isScanning = false;
						$btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Scan All Content');
						self.showNotice('Background scan completed successfully! ' + res.data.total + ' items analyzed.', 'success');
						setTimeout(function() {
							window.location.reload();
						}, 1200);
					} else {
						// Process next chunk
						setTimeout(function() {
							self.runBatchChunk($btn);
						}, 250);
					}
				} else {
					self.isScanning = false;
					$btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Scan All Content');
					self.showNotice(res.data ? res.data.message : dplsSchemaAi.i18n.error, 'error');
				}
			});
		},

		handleClearHistory: function($btn) {
			var self = this;
			if (!confirm(dplsSchemaAi.i18n.confirmClearLogs)) {
				return;
			}

			$btn.prop('disabled', true);

			$.post(dplsSchemaAi.ajaxUrl, {
				action: 'dpls_clear_history',
				nonce: dplsSchemaAi.nonce
			}, function(res) {
				$btn.prop('disabled', false);
				if (res.success) {
					self.showNotice(res.data.message, 'success');
					setTimeout(function() {
						window.location.reload();
					}, 1000);
				} else {
					self.showNotice(res.data ? res.data.message : dplsSchemaAi.i18n.error, 'error');
				}
			});
		},

		// Helper to trigger client-side download of JSON blob.
		downloadJson: function(filename, dataObj) {
			var blob = new Blob([JSON.stringify(dataObj, null, 2)], { type: 'application/json' });
			var url = URL.createObjectURL(blob);
			var a = document.createElement('a');
			a.href = url;
			a.download = filename;
			document.body.appendChild(a);
			a.click();
			document.body.removeChild(a);
			URL.revokeObjectURL(url);
		},

		// Export Settings
		handleExportSettings: function($btn) {
			var self = this;
			$btn.prop('disabled', true);

			$.post(dplsSchemaAi.ajaxUrl, {
				action: 'dpls_export_settings',
				nonce: dplsSchemaAi.nonce
			}, function(res) {
				$btn.prop('disabled', false);
				if (res.success) {
					self.downloadJson(res.data.filename, res.data.data);
					self.showNotice('Settings exported successfully.', 'success');
				} else {
					self.showNotice(res.data ? res.data.message : dplsSchemaAi.i18n.error, 'error');
				}
			});
		},

		// Export Schemas
		handleExportSchemas: function($btn) {
			var self = this;
			$btn.prop('disabled', true);

			$.post(dplsSchemaAi.ajaxUrl, {
				action: 'dpls_export_schemas',
				nonce: dplsSchemaAi.nonce
			}, function(res) {
				$btn.prop('disabled', false);
				if (res.success) {
					self.downloadJson(res.data.filename, res.data.data);
					self.showNotice('Schema records exported successfully (' + res.data.data.count + ' records).', 'success');
				} else {
					self.showNotice(res.data ? res.data.message : dplsSchemaAi.i18n.error, 'error');
				}
			});
		},

		// Preview Import File
		currentImportJson: null,
		handlePreviewImport: function($btn) {
			var self = this;
			var fileInput = document.getElementById('dpls-import-file');
			if (!fileInput || !fileInput.files.length) {
				self.showNotice('Please select a JSON file to import.', 'error');
				return;
			}

			var file = fileInput.files[0];
			var reader = new FileReader();

			reader.onload = function(e) {
				var content = e.target.result;
				self.currentImportJson = content;
				$btn.prop('disabled', true).text('Validating...');

				$.post(dplsSchemaAi.ajaxUrl, {
					action: 'dpls_preview_import',
					nonce: dplsSchemaAi.nonce,
					import_json: content
				}, function(res) {
					$btn.prop('disabled', false).html('<span class="dashicons dashicons-visibility" style="vertical-align:text-bottom;"></span> Validate & Preview Import');

					if (res.success && res.data.is_valid) {
						var report = res.data;
						var html = '';

						if (report.type === 'settings') {
							html += '<div class="notice notice-info inline"><p><strong>Valid Settings Backup:</strong> Origin site: ' + (report.preview.origin_site || 'Unknown') + ', Exported: ' + report.preview.exported_at + '</p></div>';
							html += '<p>This will update organization details, enabled post types (' + report.preview.enabled_post_types.join(', ') + '), and maintenance policy (' + report.preview.automation_policy + ').</p>';
							$('#dpls-overwrite-label').hide();
						} else if (report.type === 'schemas') {
							html += '<div class="notice notice-info inline"><p><strong>Found ' + report.preview.total_in_file + ' records:</strong> ' + report.preview.valid_count + ' valid, ' + report.preview.conflict_count + ' matched posts with existing approved schemas.</p></div>';
							if (report.preview.conflict_count > 0) {
								$('#dpls-overwrite-label').show();
							} else {
								$('#dpls-overwrite-label').hide();
							}
						}

						if (report.errors && report.errors.length) {
							html += '<div class="notice notice-warning inline"><p><strong>Warnings/Rejections:</strong></p><ul>';
							$.each(report.errors, function(i, err) {
								html += '<li>' + $('<div>').text(err).html() + '</li>';
							});
							html += '</ul></div>';
						}

						$('#dpls-import-preview-content').html(html);
						$('#dpls-import-preview-box').fadeIn(200);
					} else {
						var errMsg = res.data && res.data.errors ? res.data.errors.join('<br>') : (res.data ? res.data.message : dplsSchemaAi.i18n.error);
						self.showNotice(errMsg, 'error');
					}
				});
			};

			reader.readAsText(file);
		},

		// Execute Import
		handleExecuteImport: function($btn) {
			var self = this;
			if (!self.currentImportJson) {
				self.showNotice('No import data loaded.', 'error');
				return;
			}

			if (!confirm('Are you sure you want to apply this import to your website?')) {
				return;
			}

			$btn.prop('disabled', true).text('Applying...');

			$.post(dplsSchemaAi.ajaxUrl, {
				action: 'dpls_execute_import',
				nonce: dplsSchemaAi.nonce,
				import_json: self.currentImportJson,
				confirmed: 1,
				overwrite: $('#dpls-import-overwrite').is(':checked') ? 1 : 0
			}, function(res) {
				$btn.prop('disabled', false).text('Confirm & Apply Import');

				if (res.success) {
					self.showNotice(res.data.message, 'success');
					$('#dpls-import-preview-box').fadeOut(200);
					setTimeout(function() {
						window.location.reload();
					}, 1200);
				} else {
					self.showNotice(res.data ? res.data.message : dplsSchemaAi.i18n.error, 'error');
				}
			});
		}
	};

	$(document).ready(function() {
		DplsAdmin.init();

		// Bind Import / Export Buttons
		$(document).on('click', '#dpls-export-settings-btn', function(e) {
			e.preventDefault();
			DplsAdmin.handleExportSettings($(this));
		});

		$(document).on('click', '#dpls-export-schemas-btn', function(e) {
			e.preventDefault();
			DplsAdmin.handleExportSchemas($(this));
		});

		$(document).on('change', '#dpls-import-file', function() {
			if ($(this).val()) {
				$('#dpls-preview-import-btn').prop('disabled', false);
			} else {
				$('#dpls-preview-import-btn').prop('disabled', true);
			}
		});

		$(document).on('click', '#dpls-preview-import-btn', function(e) {
			e.preventDefault();
			DplsAdmin.handlePreviewImport($(this));
		});

		$(document).on('click', '#dpls-confirm-import-btn', function(e) {
			e.preventDefault();
			DplsAdmin.handleExecuteImport($(this));
		});

		$(document).on('click', '#dpls-cancel-import-btn', function(e) {
			e.preventDefault();
			$('#dpls-import-preview-box').fadeOut(150);
		});
	});

})(jQuery);
