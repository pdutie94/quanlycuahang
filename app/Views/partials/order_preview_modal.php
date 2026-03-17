<div class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/40 p-3" data-order-preview-modal>
	<div class="flex w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl" style="height: min(85vh, 48rem);">
		<div class="flex items-center justify-between border-b border-slate-200 px-3 py-2">
			<div>
				<div class="text-sm font-semibold text-slate-800">
					<span class="order-preview-code-text" data-order-preview-code>Đơn hàng</span>
				</div>
				<div class="text-xs text-slate-500" data-order-preview-date>--</div>
			</div>
			<button type="button" class="inline-flex h-7 w-7 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-600" data-order-preview-close>
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
					<path fill-rule="evenodd" d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" />
				</svg>
			</button>
		</div>
		<div class="flex-1 min-h-0 overflow-y-auto p-3 text-sm" data-order-preview-content>
			<div class="h-full min-h-full animate-pulse" aria-hidden="true">
				<div class="space-y-3">
					<div class="flex flex-wrap items-center gap-2">
						<div class="h-6 w-24 rounded-full bg-emerald-100"></div>
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
								<div class="h-4 w-16 rounded bg-emerald-100"></div>
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
							<div class="mt-2 h-5 w-20 rounded bg-emerald-100"></div>
						</div>
						<div class="rounded-lg bg-white px-2.5 py-2 ring-1 ring-slate-200">
							<div class="h-3 w-12 rounded bg-slate-200"></div>
							<div class="mt-2 h-5 w-16 rounded bg-slate-300"></div>
						</div>
					</div>

					<div class="rounded-lg border border-slate-200 bg-white shadow-sm">
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
		<div class="flex items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-3 py-2">
			<a href="#" class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-emerald-700" data-order-preview-open-detail>Xem chi tiết</a>
			<button type="button" class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100" data-order-preview-close>Đóng</button>
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
						'<div class="h-6 w-24 rounded-full bg-emerald-100"></div>',
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
								'<div class="h-4 w-16 rounded bg-emerald-100"></div>',
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
							'<div class="mt-2 h-5 w-20 rounded bg-emerald-100"></div>',
						'</div>',
						'<div class="rounded-lg bg-white px-2.5 py-2 ring-1 ring-slate-200">',
							'<div class="h-3 w-12 rounded bg-slate-200"></div>',
							'<div class="mt-2 h-5 w-16 rounded bg-slate-300"></div>',
						'</div>',
					'</div>',
					'<div class="rounded-lg border border-slate-200 bg-white shadow-sm">',
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
