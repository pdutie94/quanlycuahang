<div class="app-modal-overlay z-[99999]" data-order-preview-modal>
	<div class="app-modal-sheet">
		<div class="app-modal-header">
			<div>
				<div class="text-sm font-semibold text-slate-800">
					<span class="order-preview-code-text" data-order-preview-code>Đơn hàng</span>
				</div>
				<div class="text-xs text-slate-500" data-order-preview-date>--</div>
			</div>
			<button type="button" class="app-modal-close" data-order-preview-close>
				<?php echo ui_icon("x-mark", "h-4 w-4"); ?>
			</button>
		</div>
		<div class="flex-1 min-h-0 overflow-y-auto p-3 text-sm" data-order-preview-content>
			<div class="h-full min-h-full animate-pulse" aria-hidden="true">
				<div class="space-y-3">
					<div class="flex flex-wrap items-center gap-2">
						<div class="h-6 w-24 rounded-full bg-brand-100"></div>
						<div class="h-6 w-20 rounded-full bg-amber-100"></div>
					</div>

					<div class="rounded-lg bg-white">
						<div class="space-y-2">
							<div class="flex items-center justify-between gap-3">
								<div class="h-3 w-16 rounded bg-slate-200"></div>
								<div class="h-4 w-20 rounded bg-slate-300"></div>
							</div>
							<div class="flex items-center justify-between gap-3">
								<div class="h-3 w-14 rounded bg-slate-200"></div>
								<div class="h-4 w-16 rounded bg-brand-100"></div>
							</div>
							<div class="flex items-center justify-between gap-3">
								<div class="h-3 w-12 rounded bg-slate-200"></div>
								<div class="h-4 w-16 rounded bg-amber-100"></div>
							</div>
						</div>
						<div class="my-2 border-t border-dashed border-slate-200"></div>
						<div class="flex items-center justify-between gap-3">
							<div class="h-4 w-20 rounded bg-slate-300"></div>
							<div class="h-5 w-24 rounded bg-slate-300"></div>
						</div>
					</div>

					<div class="grid grid-cols-2 gap-3">
						<div class="rounded-lg bg-white px-2.5 py-2 ring-1 ring-slate-200">
							<div class="h-3 w-16 rounded bg-slate-200"></div>
							<div class="mt-2 h-5 w-20 rounded bg-brand-100"></div>
						</div>
						<div class="rounded-lg bg-white px-2.5 py-2 ring-1 ring-slate-200">
							<div class="h-3 w-12 rounded bg-slate-200"></div>
							<div class="mt-2 h-5 w-16 rounded bg-slate-300"></div>
						</div>
					</div>

					<div class="rounded-lg border border-slate-200 bg-white ">
						<div class="border-b border-slate-100 px-3 py-2">
							<div class="h-4 w-20 rounded bg-slate-300"></div>
						</div>
						<div class="divide-y divide-slate-100">
							<div class="flex items-center justify-between px-3 py-2">
								<div class="space-y-2">
									<div class="h-4 w-36 rounded bg-slate-300"></div>
									<div class="h-3 w-44 rounded bg-slate-200"></div>
								</div>
								<div class="h-4 w-16 rounded bg-slate-300"></div>
							</div>
							<div class="flex items-center justify-between px-3 py-2">
								<div class="space-y-2">
									<div class="h-4 w-32 rounded bg-slate-300"></div>
									<div class="h-3 w-40 rounded bg-slate-200"></div>
								</div>
								<div class="h-4 w-14 rounded bg-slate-300"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="app-modal-footer bg-slate-50">
			<a href="#" class="app-btn-primary" data-order-preview-open-detail>Xem chi tiết</a>
			<button type="button" class="app-btn-secondary" data-order-preview-close>Đóng</button>
		</div>
	</div>
</div>
<script>
(function () {
	var modal = document.querySelector('[data-order-preview-modal]');
	var modalContent = modal ? modal.querySelector('[data-order-preview-content]') : null;
	var minimumLoadingMs = 1000;
	var activePreviewRequestId = 0;

	function loadingHtml() {
		return [
			'<div class="h-full min-h-full animate-pulse" aria-hidden="true">',
				'<div class="space-y-3">',
					'<div class="flex flex-wrap items-center gap-2">',
						'<div class="h-6 w-24 rounded-full bg-brand-100"></div>',
						'<div class="h-6 w-20 rounded-full bg-amber-100"></div>',
					'</div>',
					'<div class="rounded-lg bg-white">',
						'<div class="space-y-2">',
							'<div class="flex items-center justify-between gap-3">',
								'<div class="h-3 w-16 rounded bg-slate-200"></div>',
								'<div class="h-4 w-20 rounded bg-slate-300"></div>',
							'</div>',
							'<div class="flex items-center justify-between gap-3">',
								'<div class="h-3 w-14 rounded bg-slate-200"></div>',
								'<div class="h-4 w-16 rounded bg-brand-100"></div>',
							'</div>',
							'<div class="flex items-center justify-between gap-3">',
								'<div class="h-3 w-12 rounded bg-slate-200"></div>',
								'<div class="h-4 w-16 rounded bg-amber-100"></div>',
							'</div>',
						'</div>',
						'<div class="my-2 border-t border-dashed border-slate-200"></div>',
						'<div class="flex items-center justify-between gap-3">',
							'<div class="h-4 w-20 rounded bg-slate-300"></div>',
							'<div class="h-5 w-24 rounded bg-slate-300"></div>',
						'</div>',
					'</div>',
					'<div class="grid grid-cols-2 gap-3">',
						'<div class="rounded-lg bg-white px-2.5 py-2 ring-1 ring-slate-200">',
							'<div class="h-3 w-16 rounded bg-slate-200"></div>',
							'<div class="mt-2 h-5 w-20 rounded bg-brand-100"></div>',
						'</div>',
						'<div class="rounded-lg bg-white px-2.5 py-2 ring-1 ring-slate-200">',
							'<div class="h-3 w-12 rounded bg-slate-200"></div>',
							'<div class="mt-2 h-5 w-16 rounded bg-slate-300"></div>',
						'</div>',
					'</div>',
					'<div class="rounded-lg border border-slate-200 bg-white ">',
						'<div class="border-b border-slate-100 px-3 py-2">',
							'<div class="h-4 w-20 rounded bg-slate-300"></div>',
						'</div>',
						'<div class="divide-y divide-slate-100">',
							'<div class="flex items-center justify-between px-3 py-2">',
								'<div class="space-y-2">',
									'<div class="h-4 w-36 rounded bg-slate-300"></div>',
									'<div class="h-3 w-44 rounded bg-slate-200"></div>',
								'</div>',
								'<div class="h-4 w-16 rounded bg-slate-300"></div>',
							'</div>',
							'<div class="flex items-center justify-between px-3 py-2">',
								'<div class="space-y-2">',
									'<div class="h-4 w-32 rounded bg-slate-300"></div>',
									'<div class="h-3 w-40 rounded bg-slate-200"></div>',
								'</div>',
								'<div class="h-4 w-14 rounded bg-slate-300"></div>',
							'</div>',
						'</div>',
					'</div>',
				'</div>',
			'</div>'
		].join('');
	}

	function openPreview(html) {
		if (!modal) return;
		modalContent.innerHTML = html;
		modal.classList.remove('hidden');
		modal.classList.add('flex');
	}

	function updatePreviewWhenReady(requestId, startedAt, render) {
		var elapsed = Date.now() - startedAt;
		var remaining = Math.max(0, minimumLoadingMs - elapsed);
		window.setTimeout(function () {
			if (!modalContent || requestId !== activePreviewRequestId) return;
			render();
		}, remaining);
	}

	function closePreview() {
		if (!modal) return;
		activePreviewRequestId += 1;
		modal.classList.add('hidden');
		modal.classList.remove('flex');
	}

	if (modal) {
		modal.querySelectorAll('[data-order-preview-close]').forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				closePreview();
			});
		});
		modal.addEventListener('click', function (e) {
			if (e.target === modal) {
				closePreview();
			}
		});
	}

	document.addEventListener('keydown', function (e) {
		if (!modal || modal.classList.contains('hidden')) return;
		if (e.key === 'Escape') {
			e.preventDefault();
			closePreview();
		}
	});

	// Use event delegation to handle both existing and dynamically loaded buttons.
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-order-preview-btn]');
		if (!btn) return;
		e.preventDefault();
		e.stopPropagation();
		var orderId = btn.getAttribute('data-order-id');
		if (!orderId || !modalContent) return;
		var requestId = activePreviewRequestId + 1;
		var loadingStartedAt = Date.now();
		var orderCode = btn.getAttribute('data-order-code') || 'Đơn hàng';
		var orderCustomer = (btn.getAttribute('data-order-customer') || '').trim();
		var normalizedCustomer = orderCustomer.toLowerCase();
		var orderDate = btn.getAttribute('data-order-date') || '';
		var codeElem = document.querySelector('[data-order-preview-code]');
		var dateElem = document.querySelector('[data-order-preview-date]');
		var detailBtn = modal ? modal.querySelector('[data-order-preview-open-detail]') : null;
		if (codeElem) {
			var headerTitle = orderCode;
			if (orderCustomer !== '' && normalizedCustomer !== 'khách lẻ') {
				headerTitle += ' - ' + orderCustomer;
			}
			codeElem.textContent = headerTitle;
		}
		if (dateElem) dateElem.textContent = orderDate;
		if (detailBtn) {
			detailBtn.setAttribute('href', '<?php echo $basePath; ?>/order/view?id=' + encodeURIComponent(orderId));
		}
		activePreviewRequestId = requestId;
		openPreview(loadingHtml());
		fetch('<?php echo $basePath; ?>/order/preview?id=' + encodeURIComponent(orderId) + '&ajax=1', {
			method: 'GET',
			credentials: 'same-origin',
			headers: { 'X-Requested-With': 'XMLHttpRequest' }
		})
			.then(function (response) {
				if (!response.ok) {
					throw new Error('Lỗi khi tải dữ liệu: ' + response.status);
				}
				return response.text();
			})
			.then(function (html) {
				updatePreviewWhenReady(requestId, loadingStartedAt, function () {
					openPreview(html);
				});
			})
			.catch(function (error) {
				updatePreviewWhenReady(requestId, loadingStartedAt, function () {
					if (modalContent) {
						modalContent.innerHTML = '<div class="py-6 text-center text-rose-600">' + (error.message || 'Không thể hiển thị dữ liệu') + '</div>';
					}
				});
			});
	});
})();
</script>
