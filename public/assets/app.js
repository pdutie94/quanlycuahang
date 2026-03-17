;(function ($) {
    var appToastContainer = null;

    function createToastContainer() {
        if (appToastContainer) return appToastContainer;
        appToastContainer = $('<div>', {
            id: 'app-toast-container',
            "class": 'fixed inset-x-0 bottom-20 z-50 flex flex-col items-center space-y-2 px-4'
        }).appendTo('body');
        return appToastContainer;
    }

    window.showToast = function (type, message) {
        if (!message) return;
        var $container = createToastContainer();
        var base = 'pointer-events-auto flex items-center gap-2 rounded-lg px-3 py-2 text-sm ring-1 transition transform';
        var color;
        if (type === 'error') {
            color = ' bg-red-600 text-white ring-red-500/70';
        } else if (type === 'success') {
            color = ' bg-emerald-600 text-white ring-emerald-500/70';
        } else {
            color = ' bg-slate-800 text-white ring-slate-700/70';
        }
        var $el = $('<div>', {
            "class": base + color,
            text: message
        }).css({
            opacity: 0,
            transform: 'translateY(16px)'
        });
        $container.append($el);
        requestAnimationFrame(function () {
            $el.css({
                opacity: 1,
                transform: 'translateY(0)'
            });
        });
        setTimeout(function () {
            $el.css({
                opacity: 0,
                transform: 'translateY(16px)'
            });
            setTimeout(function () {
                $el.remove();
            }, 200);
        }, 3000);
    };

    function initLoadingButtons() {
        $(document).on('submit', 'form', function () {
            var $form = $(this);
            var $buttons = $form.find('[data-loading-button]');
            if (!$buttons.length) return;
            $buttons.each(function () {
                var $btn = $(this);
                $btn.prop('disabled', true);
                if ($btn.data('loading-applied')) {
                    return;
                }
                var originalHtml = $btn.html();
                $btn.data('loading-original', originalHtml);
                var spinner = '<span class="mr-1 inline-block h-4 w-4 animate-spin rounded-full border-2 border-white/60 border-t-transparent align-middle"></span>';
                $btn.html(spinner + originalHtml);
                $btn.data('loading-applied', true);
            });
        });
    }

    function updateUnitRowDeleteButtons() {
        var $container = $('[data-unit-rows]');
        if (!$container.length) return;
        var $wrappers = $container.find('.unit-row-wrapper');
        $wrappers.each(function () {
            var $wrapper = $(this);
            var $btn = $wrapper.find('[data-remove-unit-row]');
            if (!$btn.length) return;
            if ($wrappers.length <= 1) {
                $btn.prop('disabled', true).addClass('opacity-50 cursor-not-allowed');
            } else {
                $btn.prop('disabled', false).removeClass('opacity-50 cursor-not-allowed');
            }
        });
    }

    function initUnitRows() {
        $(document).on('click', '[data-add-unit-row]', function (e) {
            e.preventDefault();
            var $container = $('[data-unit-rows]');
            if (!$container.length) return;
            var $baseRow = $container.find('.unit-row-wrapper').first();
            if (!$baseRow.length) return;
            var $clone = $baseRow.clone(true);
            $clone.find('input').val('');
            $clone.find('select').each(function () {
                this.selectedIndex = 0;
            });
            $container.append($clone);
            updateUnitRowDeleteButtons();
        });

        $(document).on('click', '[data-remove-unit-row]', function (e) {
            e.preventDefault();
            var $container = $('[data-unit-rows]');
            if (!$container.length) return;
            var $wrappers = $container.find('.unit-row-wrapper');
            if ($wrappers.length <= 1) return;
            var $wrapper = $(this).closest('.unit-row-wrapper');
            if ($wrapper.length) {
                $wrapper.remove();
                updateUnitRowDeleteButtons();
            }
        });

        updateUnitRowDeleteButtons();
    }

    function updatePurchaseItemRowDeleteButtons() {
        var $container = $('[data-purchase-item-rows]');
        if (!$container.length) return;
        var $rows = $container.find('.purchase-item-row');
        $rows.each(function () {
            var $row = $(this);
            var $btn = $row.find('[data-purchase-remove-row]');
            if (!$btn.length) return;
            if ($rows.length <= 1) {
                $btn.prop('disabled', true).addClass('opacity-50 cursor-not-allowed');
            } else {
                $btn.prop('disabled', false).removeClass('opacity-50 cursor-not-allowed');
            }
        });
    }

    function parsePurchaseMoney(value) {
        if (value === null || typeof value === 'undefined') return 0;
        var digits = String(value).replace(/[^0-9]/g, '');
        if (!digits) return 0;
        var number = parseFloat(digits);
        if (!isFinite(number) || number < 0) return 0;
        return number;
    }

    function recalcPurchaseTotals() {
        var $container = $('[data-purchase-item-rows]');
        if (!$container.length) return;

        var totalQty = 0;
        var totalAmount = 0;

        $container.find('.purchase-item-row').each(function () {
            var $row = $(this);
            var qtyRaw = $row.find('input[name="qty[]"]').val() || '';
            var qty = parseFloat(qtyRaw.toString().replace(',', '.'));
            if (!isFinite(qty) || qty <= 0) {
                qty = 0;
            }

            var priceRaw = $row.find('input[name="price_cost[]"]').val() || '';
            var price = parsePurchaseMoney(priceRaw);

            var amountInputVal = '';
            var $amountInput = $row.find('[data-purchase-amount-input]');
            if ($amountInput.length) {
                amountInputVal = $amountInput.val() || '';
            }
            var amountFromInput = parsePurchaseMoney(amountInputVal);

            var amount = 0;
            if (amountFromInput > 0) {
                amount = amountFromInput;
            } else if (qty > 0 && price > 0) {
                amount = qty * price;
            }
            if (qty > 0 && amount > 0) {
                totalQty += qty;
                totalAmount += amount;
            }

            var $lineTotal = $row.find('[data-purchase-line-total]');
            if ($lineTotal.length) {
                if (amount > 0) {
                    $lineTotal.text(amount.toLocaleString('vi-VN') + ' đ');
                } else {
                    $lineTotal.text('0 đ');
                }
            }
        });

        var $qtySummary = $('[data-purchase-summary-qty]');
        if ($qtySummary.length) {
            $qtySummary.text(totalQty > 0 ? totalQty : 0);
        }

        var $amountSummary = $('[data-purchase-summary-amount]');
        if ($amountSummary.length) {
            if (totalAmount > 0) {
                $amountSummary.text(totalAmount.toLocaleString('vi-VN') + ' đ');
            } else {
                $amountSummary.text('0 đ');
            }
        }
    }

    function initPurchaseItemRows() {
        registerProductSelectorMode('purchase-add', {
            buildItems: function () {
                var source = window.PURCHASE_PRODUCT_UNITS || [];
                var items = [];
                $.each(source, function (index, row) {
                    if (!row || typeof row.id === 'undefined') return;
                    var name = row.product_name || '';
                    var unit = row.unit_name || '';
                    var label = name;
                    if (unit) {
                        label = name ? (name + ' - ' + unit) : unit;
                    }
                    var priceText = row.price_text || '';
                    items.push({
                        id: row.id,
                        label: label,
                        sub: priceText,
                        image: row.image_url || null,
                        price_cost: row.price_cost || 0
                    });
                });
                return items;
            },
            onConfirm: function (items) {
                if (!items || !items.length) return;
                var $container = $('[data-purchase-item-rows]');
                if (!$container.length) return;

                var $emptyState = $('[data-purchase-empty]');
                if ($emptyState.length) {
                    $emptyState.addClass('hidden');
                }
                $container.removeClass('hidden');

                var existingIds = {};
                $container.find('input[name="product_unit_id[]"]').each(function () {
                    var val = $(this).val();
                    if (val) {
                        existingIds[String(val)] = true;
                    }
                });

                var $baseRow = $container.find('.purchase-item-row').first();
                if (!$baseRow.length) return;

                function applyItemToRow($row, item) {
                    if (!$row || !item) return;
                    var unitId = item.id;
                    var price = parseFloat(item.price_cost || 0);
                    if (!isFinite(price) || price < 0) {
                        price = 0;
                    }

                    var $unitInput = $row.find('input[name="product_unit_id[]"]');
                    if (!$unitInput.length) {
                        $unitInput = $('<input>', {
                            type: 'hidden',
                            name: 'product_unit_id[]'
                        });
                        var $label = $row.find('[data-purchase-product-label]');
                        if ($label.length) {
                            $label.before($unitInput);
                        } else {
                            $row.prepend($unitInput);
                        }
                    }
                    $unitInput.val(unitId);

                    var $labelEl = $row.find('[data-purchase-product-label]');
                    if ($labelEl.length) {
                        $labelEl.text(item.label || '');
                    }
                    var $subEl = $row.find('[data-purchase-product-sub]');
                    if ($subEl.length) {
                        $subEl.text(item.sub || '');
                    }

                    var $qtyInput = $row.find('input[name="qty[]"]');
                    if ($qtyInput.length && (!$qtyInput.val() || $qtyInput.val() === '0')) {
                        $qtyInput.val('1');
                    }

                    var $priceInput = $row.find('input[name="price_cost[]"]');
                    if ($priceInput.length && price > 0 && !$priceInput.val()) {
                        $priceInput.val(price.toLocaleString('vi-VN'));
                    }
                }

                $.each(items, function (index, item) {
                    if (!item || typeof item.id === 'undefined') return;
                    var idStr = String(item.id);
                    if (existingIds[idStr]) {
                        return;
                    }
                    var $targetRow = null;
                    var $emptyRow = $container.find('.purchase-item-row').filter(function () {
                        var v = $(this).find('input[name="product_unit_id[]"]').val();
                        return !v;
                    }).first();
                    if ($emptyRow.length) {
                        $targetRow = $emptyRow;
                    } else {
                        $targetRow = $baseRow.clone(false);
                        $targetRow.find('input[name="qty[]"]').val('');
                        $targetRow.find('input[name="price_cost[]"]').val('');
                        $targetRow.find('input[name="product_unit_id[]"]').val('');
                        $targetRow.find('[data-purchase-product-label]').text('Chưa chọn sản phẩm');
                        $targetRow.find('[data-purchase-product-sub]').text('');
                        $container.append($targetRow);
                    }
                    $targetRow.removeClass('hidden');
                    applyItemToRow($targetRow, item);
                    existingIds[idStr] = true;
                });

                updatePurchaseItemRowDeleteButtons();
                recalcPurchaseTotals();
            }
        });

        $(document).on('click', '[data-purchase-remove-row]', function (e) {
            e.preventDefault();
            var $container = $('[data-purchase-item-rows]');
            if (!$container.length) return;
            var $rows = $container.find('.purchase-item-row');
            if ($rows.length <= 1) return;
            var $row = $(this).closest('.purchase-item-row');
            if ($row.length) {
                $row.remove();
                updatePurchaseItemRowDeleteButtons();
                recalcPurchaseTotals();
            }
        });

        $(document).on('input change', '[data-purchase-item-rows] input[name="qty[]"]', function () {
            var $qtyInput = $(this);
            var $row = $qtyInput.closest('.purchase-item-row');
            var qtyRaw = $qtyInput.val() || '';
            var qty = parseFloat(qtyRaw.toString().replace(',', '.'));
            if (!isFinite(qty) || qty <= 0) {
                qty = 0;
            }

            var $amountInput = $row.find('[data-purchase-amount-input]');
            var $priceInput = $row.find('input[name="price_cost[]"]');
            var amountVal = $amountInput.length ? parsePurchaseMoney($amountInput.val() || '') : 0;
            var priceVal = $priceInput.length ? parsePurchaseMoney($priceInput.val() || '') : 0;
            var isManual = $row.data('purchaseAmountManual') === true;

            if (qty > 0) {
                if (isManual && amountVal > 0 && $priceInput.length) {
                    var newPrice = amountVal / qty;
                    if (isFinite(newPrice) && newPrice > 0) {
                        $priceInput.val(newPrice.toLocaleString('vi-VN'));
                    }
                } else if (!isManual && priceVal > 0 && $amountInput.length) {
                    var newAmount = qty * priceVal;
                    if (isFinite(newAmount) && newAmount > 0) {
                        $amountInput.val(newAmount.toLocaleString('vi-VN'));
                    }
                }
            }

            recalcPurchaseTotals();
        });

        $(document).on('input change', '[data-purchase-item-rows] input[name="price_cost[]"]', function () {
            var $priceInput = $(this);
            var $row = $priceInput.closest('.purchase-item-row');
            $row.data('purchaseAmountManual', false);

            var qtyRaw = $row.find('input[name="qty[]"]').val() || '';
            var qty = parseFloat(qtyRaw.toString().replace(',', '.'));
            if (!isFinite(qty) || qty <= 0) {
                qty = 0;
            }

            var priceVal = parsePurchaseMoney($priceInput.val() || '');
            var $amountInput = $row.find('[data-purchase-amount-input]');

            if ($amountInput.length && qty > 0 && priceVal > 0) {
                var newAmount = qty * priceVal;
                if (isFinite(newAmount) && newAmount > 0) {
                    $amountInput.val(newAmount.toLocaleString('vi-VN'));
                }
            }

            recalcPurchaseTotals();
        });

        $(document).on('input change', '[data-purchase-amount-input]', function () {
            var $amountInput = $(this);
            var $row = $amountInput.closest('.purchase-item-row');
            $row.data('purchaseAmountManual', true);

            var amountVal = parsePurchaseMoney($amountInput.val() || '');
            var qtyRaw = $row.find('input[name="qty[]"]').val() || '';
            var qty = parseFloat(qtyRaw.toString().replace(',', '.'));
            if (!isFinite(qty) || qty <= 0) {
                qty = 0;
            }

            var $priceInput = $row.find('input[name="price_cost[]"]');
            if ($priceInput.length && qty > 0 && amountVal > 0) {
                var newPrice = amountVal / qty;
                if (isFinite(newPrice) && newPrice > 0) {
                    $priceInput.val(newPrice.toLocaleString('vi-VN'));
                }
            }

            recalcPurchaseTotals();
        });

        updatePurchaseItemRowDeleteButtons();
        recalcPurchaseTotals();
    }

    function recalcTransactionTotals() {
        var $root = $('[data-transaction-form]');
        if (!$root.length) return;
        var $container = $root.find('[data-transaction-item-rows]');
        if (!$container.length) return;

        var totalBuy = 0;
        var totalSell = 0;

        $container.find('.transaction-item-row').each(function () {
            var $row = $(this);
            var name = $row.find('input[name="item_name[]"]').val() || '';
            var unit = $row.find('input[name="unit_name[]"]').val() || '';
            var qtyRaw = $row.find('input[name="qty[]"]').val() || '';
            var qty = parseFloat(qtyRaw.toString().replace(',', '.'));
            if (!isFinite(qty) || qty <= 0) {
                qty = 0;
            }

            var priceBuyRaw = $row.find('input[name="price_buy[]"]').val() || '';
            var priceSellRaw = $row.find('input[name="price_sell[]"]').val() || '';
            var priceBuy = parsePurchaseMoney(priceBuyRaw);
            var priceSell = parsePurchaseMoney(priceSellRaw);

            var amountBuy = 0;
            var amountSell = 0;
            if (qty > 0 && priceBuy > 0) {
                amountBuy = qty * priceBuy;
            }
            if (qty > 0 && priceSell > 0) {
                amountSell = qty * priceSell;
            }

            if (amountBuy > 0) {
                totalBuy += amountBuy;
            }
            if (amountSell > 0) {
                totalSell += amountSell;
            }

            var $buyTotal = $row.find('[data-transaction-line-buy-total]');
            if ($buyTotal.length) {
                if (amountBuy > 0) {
                    $buyTotal.text(formatCurrency(amountBuy) + ' đ');
                } else {
                    $buyTotal.text('0 đ');
                }
            }
            var $sellTotal = $row.find('[data-transaction-line-sell-total]');
            if ($sellTotal.length) {
                if (amountSell > 0) {
                    $sellTotal.text(formatCurrency(amountSell) + ' đ');
                } else {
                    $sellTotal.text('0 đ');
                }
            }

            var $displayName = $row.find('[data-transaction-display-name]');
            if ($displayName.length) {
                $displayName.text(name || 'Chưa nhập tên hàng');
            }
            var $displayUnit = $row.find('[data-transaction-display-unit]');
            if ($displayUnit.length) {
                $displayUnit.text(unit || '');
            }
            var $displayQty = $row.find('[data-transaction-display-qty]');
            if ($displayQty.length) {
                if (qty > 0) {
                    $displayQty.text(qtyRaw);
                } else {
                    $displayQty.text('0');
                }
            }
            var $displayBuy = $row.find('[data-transaction-display-buy]');
            if ($displayBuy.length) {
                if (priceBuy > 0) {
                    $displayBuy.text(formatCurrency(priceBuy) + ' đ');
                } else {
                    $displayBuy.text('0 đ');
                }
            }
            var $displaySell = $row.find('[data-transaction-display-sell]');
            if ($displaySell.length) {
                if (priceSell > 0) {
                    $displaySell.text(formatCurrency(priceSell) + ' đ');
                } else {
                    $displaySell.text('0 đ');
                }
            }
        });

        var discountAmount = 0;
        var surchargeAmount = 0;
        var $discountTypeInput = $root.find('[data-order-discount-type]');
        var $discountValueInput = $root.find('[data-order-discount-value]');
        var discountType = $discountTypeInput.length ? ($discountTypeInput.val() || 'none') : 'none';
        var discountValue = $discountValueInput.length ? parseFloat($discountValueInput.val() || '0') : 0;
        if (!isFinite(discountValue) || discountValue < 0) {
            discountValue = 0;
        }
        if (discountType === 'fixed') {
            discountAmount = discountValue;
        } else if (discountType === 'percent') {
            if (discountValue > 100) {
                discountValue = 100;
            }
            discountAmount = Math.round(totalSell * discountValue / 100);
        }
        if (!isFinite(discountAmount) || discountAmount < 0) {
            discountAmount = 0;
        }
        if (discountAmount > totalSell) {
            discountAmount = totalSell;
        }

        var $surchargeHidden = $root.find('[data-transaction-surcharge-value]');
        if ($surchargeHidden.length) {
            var surchargeRaw = $surchargeHidden.val() || '0';
            surchargeAmount = parsePurchaseMoney(surchargeRaw);
            if (!isFinite(surchargeAmount) || surchargeAmount < 0) {
                surchargeAmount = 0;
            }
        }

        var finalSell = totalSell - discountAmount + surchargeAmount;
        var profit = finalSell - totalBuy;

        var $buySummary = $root.find('[data-transaction-summary-buy]');
        if ($buySummary.length) {
            $buySummary.text(formatCurrency(totalBuy) + ' đ');
        }
        var $subtotalSummary = $root.find('[data-transaction-summary-subtotal]');
        if ($subtotalSummary.length) {
            $subtotalSummary.text(formatCurrency(totalSell) + ' đ');
        }
        var $sellSummary = $root.find('[data-transaction-summary-sell]');
        if ($sellSummary.length) {
            $sellSummary.text(formatCurrency(finalSell) + ' đ');
        }
        var $profitSummary = $root.find('[data-transaction-summary-profit]');
        if ($profitSummary.length) {
            $profitSummary.text(formatCurrency(profit) + ' đ');
        }

        var $discountDisplay = $root.find('[data-transaction-discount-amount]');
        if ($discountDisplay.length) {
            if (discountAmount > 0) {
                $discountDisplay.text('-' + formatCurrency(discountAmount) + ' đ');
            } else {
                $discountDisplay.text('-0 đ');
            }
        }
        var $surchargeDisplay = $root.find('[data-transaction-surcharge-amount]');
        if ($surchargeDisplay.length) {
            if (surchargeAmount > 0) {
                $surchargeDisplay.text('+' + formatCurrency(surchargeAmount) + ' đ');
            } else {
                $surchargeDisplay.text('+0 đ');
            }
        }
    }

    function initTransactionForm() {
        var $root = $('[data-transaction-form]');
        if (!$root.length) return;

        var $container = $root.find('[data-transaction-item-rows]');
        if (!$container.length) return;

        var $customerSelect = $root.find('select[name="customer_id"]');
        var $customerExtra = $root.find('[data-transaction-customer-extra]');
        var $modal = $('[data-transaction-edit-modal]');
        var currentRow = null;

        function toggleCustomerExtra() {
            if (!$customerSelect.length || !$customerExtra.length) return;
            var value = $customerSelect.val();
            var show = !value;
            $customerExtra.toggleClass('hidden', !show);
        }

        if ($customerSelect.length && $customerExtra.length) {
            $customerSelect.on('change', function () {
                toggleCustomerExtra();
            });
            toggleCustomerExtra();
        }

        if ($modal.length) {
            var $editName = $modal.find('[data-transaction-edit-name]');
            var $editUnit = $modal.find('[data-transaction-edit-unit]');
            var $editQty = $modal.find('[data-transaction-edit-qty]');
            var $editPriceBuy = $modal.find('[data-transaction-edit-price-buy]');
            var $editAmountBuy = $modal.find('[data-transaction-edit-amount-buy]');
            var $editPriceSell = $modal.find('[data-transaction-edit-price-sell]');

            function openEditModal($row) {
                currentRow = $row;
                if (!currentRow || !currentRow.length) return;
                var name = currentRow.find('input[name="item_name[]"]').val() || '';
                var unit = currentRow.find('input[name="unit_name[]"]').val() || '';
                var qty = currentRow.find('input[name="qty[]"]').val() || '';
                var priceBuy = currentRow.find('input[name="price_buy[]"]').val() || '';
                var priceSell = currentRow.find('input[name="price_sell[]"]').val() || '';

                $editName.val(name);
                $editUnit.val(unit);
                $editQty.val(qty);
                $editPriceBuy.val(priceBuy);
                $editPriceSell.val(priceSell);

                if ($editAmountBuy.length) {
                    var qtyNum = parseFloat((qty || '').toString().replace(',', '.'));
                    if (!isFinite(qtyNum) || qtyNum <= 0) {
                        qtyNum = 0;
                    }
                    var priceBuyNum = parsePurchaseMoney(priceBuy);
                    var amountBuy = 0;
                    if (qtyNum > 0 && priceBuyNum > 0) {
                        amountBuy = qtyNum * priceBuyNum;
                    }
                    if (amountBuy > 0) {
                        $editAmountBuy.val(formatCurrency(amountBuy));
                    } else {
                        $editAmountBuy.val('');
                    }
                }

                $modal.removeClass('hidden').addClass('flex');
            }

            function closeEditModal() {
                $modal.addClass('hidden').removeClass('flex');
                currentRow = null;
            }

            $(document).on('click', '.transaction-item-row', function (e) {
                if ($(e.target).closest('[data-transaction-remove-row]').length) {
                    return;
                }
                e.preventDefault();
                openEditModal($(this));
            });

            $(document).on('click', '[data-transaction-edit-cancel]', function (e) {
                e.preventDefault();
                closeEditModal();
            });

            $(document).on('click', '[data-transaction-edit-save]', function (e) {
                e.preventDefault();
                if (!currentRow || !currentRow.length) {
                    closeEditModal();
                    return;
                }
                var nameVal = $editName.val() || '';
                var unitVal = $editUnit.val() || '';
                var qtyVal = $editQty.val() || '';
                var priceBuyVal = $editPriceBuy.val() || '';
                var amountBuyVal = $editAmountBuy.length ? $editAmountBuy.val() || '' : '';
                var priceSellVal = $editPriceSell.val() || '';

                var qtyNum = parseFloat(qtyVal.toString().replace(',', '.'));
                if (!isFinite(qtyNum) || qtyNum <= 0) {
                    qtyNum = 0;
                }
                var amountBuyNum = parsePurchaseMoney(amountBuyVal);
                var priceBuyNum = parsePurchaseMoney(priceBuyVal);

                if (qtyNum > 0 && amountBuyNum > 0) {
                    var newPriceBuy = Math.round(amountBuyNum / qtyNum);
                    if (isFinite(newPriceBuy) && newPriceBuy > 0) {
                        priceBuyNum = newPriceBuy;
                        priceBuyVal = formatCurrency(priceBuyNum);
                    }
                } else if (priceBuyNum > 0 && qtyNum > 0 && $editAmountBuy.length) {
                    var recalculatedAmount = priceBuyNum * qtyNum;
                    $editAmountBuy.val(formatCurrency(recalculatedAmount));
                }

                currentRow.find('input[name="item_name[]"]').val(nameVal);
                currentRow.find('input[name="unit_name[]"]').val(unitVal);
                currentRow.find('input[name="qty[]"]').val(qtyVal);
                currentRow.find('input[name="price_buy[]"]').val(priceBuyVal);
                currentRow.find('input[name="price_sell[]"]').val(priceSellVal);
                closeEditModal();
                recalcTransactionTotals();
            });

            $modal.on('click', function (e) {
                if (e.target !== this) return;
                closeEditModal();
            });

            $(document).on('keydown', function (e) {
                if ($modal.hasClass('hidden')) return;
                if (e.key === 'Escape') {
                    e.preventDefault();
                    closeEditModal();
                }
            });

            if ($editQty.length && $editPriceBuy.length && $editAmountBuy.length) {
                var updateAmountFromPrice = function () {
                    var qtyVal = $editQty.val() || '';
                    var priceVal = $editPriceBuy.val() || '';
                    var qtyNum = parseFloat(qtyVal.toString().replace(',', '.'));
                    if (!isFinite(qtyNum) || qtyNum <= 0) {
                        qtyNum = 0;
                    }
                    var priceNum = parsePurchaseMoney(priceVal);
                    var amount = 0;
                    if (qtyNum > 0 && priceNum > 0) {
                        amount = qtyNum * priceNum;
                    }
                    if (amount > 0) {
                        $editAmountBuy.val(formatCurrency(amount));
                    } else {
                        $editAmountBuy.val('');
                    }
                };

                var updatePriceFromAmount = function () {
                    var qtyVal = $editQty.val() || '';
                    var amountVal = $editAmountBuy.val() || '';
                    var qtyNum = parseFloat(qtyVal.toString().replace(',', '.'));
                    if (!isFinite(qtyNum) || qtyNum <= 0) {
                        return;
                    }
                    var amountNum = parsePurchaseMoney(amountVal);
                    if (!isFinite(amountNum) || amountNum <= 0) {
                        return;
                    }
                    var newPrice = Math.round(amountNum / qtyNum);
                    if (isFinite(newPrice) && newPrice > 0) {
                        $editPriceBuy.val(formatCurrency(newPrice));
                    }
                };

                $editQty.on('input', updateAmountFromPrice);
                $editPriceBuy.on('input', updateAmountFromPrice);
                $editAmountBuy.on('input', updatePriceFromAmount);
            }
        }

        $(document).on('click', '[data-transaction-add-row]', function (e) {
            e.preventDefault();
            var $baseRow = $container.find('.transaction-item-row').first();
            if (!$baseRow.length) return;
            var $clone = $baseRow.clone(true);
            $clone.find('input[name="item_name[]"]').val('');
            $clone.find('input[name="unit_name[]"]').val('');
            $clone.find('input[name="qty[]"]').val('');
            $clone.find('input[name="price_buy[]"]').val('');
            $clone.find('input[name="price_sell[]"]').val('');
            $clone.find('[data-transaction-line-buy-total]').text('0 đ');
            $clone.find('[data-transaction-line-sell-total]').text('0 đ');
            $clone.find('[data-transaction-display-name]').text('Chưa nhập tên hàng');
            $clone.find('[data-transaction-display-unit]').text('');
            $clone.find('[data-transaction-display-qty]').text('0');
            $clone.find('[data-transaction-display-buy]').text('0 đ');
            $clone.find('[data-transaction-display-sell]').text('0 đ');
            $container.append($clone);
            recalcTransactionTotals();
        });

        $(document).on('click', '[data-transaction-remove-row]', function (e) {
            e.preventDefault();
            var $row = $(this).closest('.transaction-item-row');
            if (!$row.length) return;
            var $rows = $container.find('.transaction-item-row');
            if ($rows.length <= 1) {
                $row.find('input[name="item_name[]"]').val('');
                $row.find('input[name="unit_name[]"]').val('');
                $row.find('input[name="qty[]"]').val('');
                $row.find('input[name="price_buy[]"]').val('');
                $row.find('input[name="price_sell[]"]').val('');
                $row.find('[data-transaction-line-buy-total]').text('0 đ');
                $row.find('[data-transaction-line-sell-total]').text('0 đ');
                $row.find('[data-transaction-display-name]').text('Chưa nhập tên hàng');
                $row.find('[data-transaction-display-unit]').text('');
                $row.find('[data-transaction-display-qty]').text('0');
                $row.find('[data-transaction-display-buy]').text('0 đ');
                $row.find('[data-transaction-display-sell]').text('0 đ');
            } else {
                $row.remove();
            }
            recalcTransactionTotals();
        });

        $(document).on('input change', '[data-transaction-item-rows] input[name="qty[]"], [data-transaction-item-rows] input[name="price_buy[]"], [data-transaction-item-rows] input[name="price_sell[]"]', function () {
            recalcTransactionTotals();
        });

        $(document).on('click', '[data-transaction-total-round]', function (e) {
            e.preventDefault();
            var $container = $root.find('[data-transaction-item-rows]');
            if (!$container.length) return;

            var totalSell = 0;
            $container.find('.transaction-item-row').each(function () {
                var $row = $(this);
                var qtyRaw = $row.find('input[name="qty[]"]').val() || '';
                var qty = parseFloat(qtyRaw.toString().replace(',', '.'));
                if (!isFinite(qty) || qty <= 0) {
                    qty = 0;
                }
                var priceSellRaw = $row.find('input[name="price_sell[]"]').val() || '';
                var priceSell = parsePurchaseMoney(priceSellRaw);
                if (qty > 0 && priceSell > 0) {
                    totalSell += qty * priceSell;
                }
            });

            if (totalSell <= 0) {
                recalcTransactionTotals();
                return;
            }

            var $discountTypeInput = $root.find('[data-order-discount-type]');
            var $discountValueInput = $root.find('[data-order-discount-value]');
            var discountType = $discountTypeInput.length ? ($discountTypeInput.val() || 'none') : 'none';
            var discountValue = $discountValueInput.length ? parseFloat($discountValueInput.val() || '0') : 0;
            if (!isFinite(discountValue) || discountValue < 0) {
                discountValue = 0;
            }

            var currentDiscount = 0;
            if (discountType === 'fixed') {
                currentDiscount = discountValue;
            } else if (discountType === 'percent') {
                if (discountValue > 100) {
                    discountValue = 100;
                }
                currentDiscount = Math.round(totalSell * discountValue / 100);
            }
            if (!isFinite(currentDiscount) || currentDiscount < 0) {
                currentDiscount = 0;
            }
            if (currentDiscount > totalSell) {
                currentDiscount = totalSell;
            }

            var rawFinal = totalSell - currentDiscount;
            if (rawFinal < 0) {
                rawFinal = 0;
            }

            var roundedFinal = roundDownThousand(rawFinal);
            if (!isFinite(roundedFinal) || roundedFinal < 0) {
                roundedFinal = rawFinal;
            }

            var newDiscount = totalSell - roundedFinal;
            if (newDiscount < 0) {
                newDiscount = 0;
            }

            if ($discountTypeInput.length) {
                $discountTypeInput.val(newDiscount > 0 ? 'fixed' : 'none');
            }
            if ($discountValueInput.length) {
                $discountValueInput.val(newDiscount.toString());
            }

            recalcTransactionTotals();
        });

        recalcTransactionTotals();
    }

    function initProductList() {
        $(document).on('click', '[data-product-edit-row]', function (e) {
            if ($(e.target).closest('[data-product-delete]').length) {
                return;
            }
            var $row = $(this);
            var url = $row.attr('data-url') || $row.data('url');
            if (!url) return;
            window.location.href = url;
        });

		$(document).on('click', '[data-supplier-edit-row]', function (e) {
			if ($(e.target).closest('[data-supplier-delete]').length) {
				return;
			}
			var $row = $(this);
			var url = $row.attr('data-url') || $row.data('url');
			if (!url) return;
			window.location.href = url;
		});

		$(document).on('click', '[data-product-delete]', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var url = $(this).attr('href');
            if (!url) return;
            if (confirm('Xóa sản phẩm này?')) {
                window.location.href = url;
            }
        });
    }

    function initImageUpload() {
        var $imageInput = $('[data-image-input]');
        var $imagePlaceholder = $('[data-image-placeholder]');
        var $imagePreview = $('[data-image-preview]');
        var $imagePlaceholderText = $('[data-image-placeholder-text]');
        var $imageActions = $('[data-image-actions]');
        var $imageEditButton = $('[data-image-edit]');
        var $imageDeleteButton = $('[data-image-delete]');
        var $imageRemoveInput = $('[data-image-remove]');

        if ($imagePlaceholder.length && $imageInput.length) {
            $imagePlaceholder.on('click', function (e) {
                e.preventDefault();
                $imageInput.trigger('click');
            });
        }

        if ($imageEditButton.length && $imageInput.length) {
            $imageEditButton.on('click', function (e) {
                e.preventDefault();
                $imageInput.trigger('click');
            });
        }

        if ($imageDeleteButton.length && $imagePreview.length && $imagePlaceholderText.length) {
            $imageDeleteButton.on('click', function (e) {
                e.preventDefault();
                if ($imageInput.length) {
                    $imageInput.val('');
                }
                $imagePreview.attr('src', '').addClass('hidden');
                $imagePlaceholderText.removeClass('hidden');
                if ($imageActions.length) {
                    $imageActions.addClass('hidden');
                }
                if ($imageRemoveInput.length) {
                    $imageRemoveInput.val('1');
                }
            });
        }

        if ($imageInput.length && $imagePreview.length && $imagePlaceholderText.length && window.FileReader) {
            $imageInput.on('change', function () {
                var file = this.files && this.files[0];
                if (!file) return;
                if (file.type && file.type.indexOf('image/') !== 0) return;
                var reader = new FileReader();
                reader.onload = function (event) {
                    $imagePreview.attr('src', event.target.result).removeClass('hidden');
                    $imagePlaceholderText.addClass('hidden');
                    if ($imageActions.length) {
                        $imageActions.removeClass('hidden');
                    }
                    if ($imageRemoveInput.length) {
                        $imageRemoveInput.val('0');
                    }
                };
                reader.readAsDataURL(file);
            });
        }
    }

	function initProductInventoryUnit() {
		var $baseUnitSelect = $('select[name="base_unit_id"]');
		var $unitLabel = $('[data-inventory-unit-label]');
		if (!$baseUnitSelect.length || !$unitLabel.length) return;

		function syncUnitLabel() {
			var text = $baseUnitSelect.find('option:selected').text() || '';
			if (!text || text === 'Chọn đơn vị') {
				$unitLabel.text('');
				$unitLabel.addClass('hidden');
			} else {
				$unitLabel.text(text);
				$unitLabel.removeClass('hidden');
			}
		}

		syncUnitLabel();
		$baseUnitSelect.on('change', syncUnitLabel);
	}

	function initOrderPaymentModal() {
		var $modal = $('[data-order-payment-modal]');
		if (!$modal.length) return;

		var $openButtons = $('[data-order-payment-open]');
		var $closeButtons = $modal.find('[data-order-payment-close]');
		var $amountInput = $modal.find('input[name="amount"][data-money-input]');
		var $methodRadios = $modal.find('input[name="payment_method"]');

		function open() {
			$modal.removeClass('hidden').addClass('flex');
			$('body').addClass('overflow-hidden');

			if ($methodRadios.length) {
				$methodRadios.filter('[value="cash"]').prop('checked', true).trigger('change');
			}

			if ($amountInput.length) {
			}
		}

		function close() {
			$modal.addClass('hidden').removeClass('flex');
			$('body').removeClass('overflow-hidden');
		}

		$openButtons.on('click', function (e) {
			e.preventDefault();
			open();
		});

		$closeButtons.on('click', function (e) {
			e.preventDefault();
			close();
		});

		$modal.on('click', function (e) {
			if (e.target !== this) return;
			close();
		});

		$(document).on('keydown', function (e) {
			if ($modal.hasClass('hidden')) return;
			if (e.key === 'Escape') {
				e.preventDefault();
				close();
				return;
			}
			if (e.key === 'Enter') {
				var $form = $modal.find('form').first();
				if (!$form.length) return;
				var target = e.target;
				if (target && (target.tagName === 'TEXTAREA')) return;
				e.preventDefault();
				$form.trigger('submit');
			}
		});

		$(document).on('click', '[data-supplier-delete]', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var url = $(this).attr('href');
			if (!url) return;
			if (confirm('Xóa nhà cung cấp này?')) {
				window.location.href = url;
			}
		});
	}

	function initTransactionPaymentModal() {
		var $modal = $('[data-transaction-payment-modal]');
		if (!$modal.length) return;

		var $openButtons = $('[data-transaction-payment-open]');
		var $closeButtons = $modal.find('[data-transaction-payment-close]');
		var $amountInput = $modal.find('input[name="amount"][data-money-input]');
		var $methodRadios = $modal.find('input[name="payment_method"]');

		function open() {
			$modal.removeClass('hidden').addClass('flex');
			$('body').addClass('overflow-hidden');

			if ($methodRadios.length) {
				$methodRadios.filter('[value="cash"]').prop('checked', true).trigger('change');
			}

			if ($amountInput.length) {
			}
		}

		function close() {
			$modal.addClass('hidden').removeClass('flex');
			$('body').removeClass('overflow-hidden');
		}

		$openButtons.on('click', function (e) {
			e.preventDefault();
			open();
		});

		$closeButtons.on('click', function (e) {
			e.preventDefault();
			close();
		});

		$modal.on('click', function (e) {
			if (e.target !== this) return;
			close();
		});

		$(document).on('keydown', function (e) {
			if ($modal.hasClass('hidden')) return;
			if (e.key === 'Escape') {
				e.preventDefault();
				close();
				return;
			}
			if (e.key === 'Enter') {
				var $form = $modal.find('form').first();
				if (!$form.length) return;
				var target = e.target;
				if (target && (target.tagName === 'TEXTAREA')) return;
				e.preventDefault();
				$form.trigger('submit');
			}
		});
	}

	function initPurchasePaymentModal() {
		var $modal = $('[data-purchase-payment-modal]');
		if (!$modal.length) return;

		var $openButtons = $('[data-purchase-payment-open]');
		var $closeButtons = $modal.find('[data-purchase-payment-close]');
		var $amountInput = $modal.find('input[name="amount"][data-money-input]');
		var $methodRadios = $modal.find('input[name="payment_method"]');

		function open() {
			$modal.removeClass('hidden').addClass('flex');
			$('body').addClass('overflow-hidden');

			if ($methodRadios.length) {
				$methodRadios.filter('[value="cash"]').prop('checked', true).trigger('change');
			}

			if ($amountInput.length) {
			}
		}

		function close() {
			$modal.addClass('hidden').removeClass('flex');
			$('body').removeClass('overflow-hidden');
		}

		$openButtons.on('click', function (e) {
			e.preventDefault();
			open();
		});

		$closeButtons.on('click', function (e) {
			e.preventDefault();
			close();
		});

		$modal.on('click', function (e) {
			if (e.target !== this) return;
			close();
		});

		$(document).on('keydown', function (e) {
			if ($modal.hasClass('hidden')) return;
			if (e.key === 'Escape') {
				e.preventDefault();
				close();
				return;
			}
			if (e.key === 'Enter') {
				var $form = $modal.find('form').first();
				if (!$form.length) return;
				var target = e.target;
				if (target && (target.tagName === 'TEXTAREA')) return;
				e.preventDefault();
				$form.trigger('submit');
			}
		});
	}

    function initPurchasePaymentFields() {
        var $form = $('form[action$="/purchase/store"], form[action$="/purchase/update"]');
        if (!$form.length) return;

        var $statusInputs = $form.find('input[name="payment_status"]');
        var $paymentFields = $form.find('[data-purchase-payment-fields]');
        var $amountInput = $form.find('input[name="paid_amount"]');

        function toggleFields() {
            if (!$statusInputs.length || !$paymentFields.length) return;
            var current = $statusInputs.filter(':checked').val();
            var isPay = current === 'pay';
            $paymentFields.toggleClass('hidden', !isPay);
            if (!isPay && $amountInput.length) {
                $amountInput.val('');
            }
        }

        if ($statusInputs.length && $paymentFields.length) {
            $statusInputs.on('change', function () {
                toggleFields();
            });
            toggleFields();
        }
    }

	function initHeaderActionsMenu() {
		var $roots = $('[data-header-actions-root]');
		if (!$roots.length) return;

		function closeAll() {
			$('[data-header-actions-dropdown]').addClass('hidden');
		}

		$roots.each(function () {
			var $root = $(this);
			var $toggle = $root.find('[data-header-actions-toggle]').first();
			var $dropdown = $root.find('[data-header-actions-dropdown]').first();
			if (!$toggle.length || !$dropdown.length) return;

			$toggle.on('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				var willOpen = $dropdown.hasClass('hidden');
				closeAll();
				if (willOpen) {
					$dropdown.removeClass('hidden');
				}
			});

			$dropdown.on('click', function (e) {
				e.stopPropagation();
			});
		});

		$(document).on('click.headerActions', function () {
			closeAll();
		});
	}

	function initAppMenu() {
		var $overlay = $('[data-app-menu-overlay]');
		var $openButtons = $('[data-footer-menu-toggle]');
		var $closeButtons = $('[data-app-menu-close]');
		if (!$overlay.length) return;
		$openButtons.on('click', function (e) {
			e.preventDefault();
			$overlay.removeClass('hidden').addClass('flex');
			$('body').addClass('overflow-hidden');
		});
		$closeButtons.on('click', function (e) {
			e.preventDefault();
			$overlay.addClass('hidden').removeClass('flex');
			$('body').removeClass('overflow-hidden');
		});
		$overlay.on('click', function (e) {
			if (e.target !== this) return;
			$overlay.addClass('hidden').removeClass('flex');
			$('body').removeClass('overflow-hidden');
		});
	}

    function sendMigrationRequest(url, body, $button) {
        if ($button && $button.length) {
            $button.prop('disabled', true);
        }
        var csrfToken = $('body').attr('data-csrf-token') || '';
        var data = body || {};
        if (csrfToken) {
            data = $.extend({}, data, { csrf_token: csrfToken });
        }
        $.ajax({
            url: url,
            method: 'POST',
            data: data,
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function (data) {
                if (!data || !data.success) {
                    var message = data && data.message ? data.message : 'Có lỗi xảy ra khi chạy migration';
                    alert(message);
                } else {
                    window.location.reload();
                }
            },
            error: function () {
                alert('Có lỗi xảy ra khi gọi migration');
            },
            complete: function () {
                if ($button && $button.length) {
                    $button.prop('disabled', false);
                }
            }
        });
    }

    function initMigration(appBasePath) {
        var $runAllBtn = $('[data-run-migration]');
        var $runVersionButtons = $('[data-run-migration-version]');

        if ($runAllBtn.length) {
            $runAllBtn.on('click', function () {
                if ($runAllBtn.prop('disabled')) return;
                sendMigrationRequest(appBasePath + '/migration/apply', { all: 1 }, $runAllBtn);
            });
        }

        if ($runVersionButtons.length) {
            $runVersionButtons.on('click', function () {
                var $btn = $(this);
                var version = $btn.attr('data-run-migration-version');
                if (!version) return;
                sendMigrationRequest(appBasePath + '/migration/run', { version: version }, $btn);
            });
        }
    }

    function formatCurrency(value) {
        var number = typeof value === 'number' ? value : parseFloat(value || 0);
        if (!isFinite(number)) number = 0;
        return number.toLocaleString('vi-VN');
    }

    function roundDownThousand(value) {
        var number = typeof value === 'number' ? value : parseFloat(value || 0);
        if (!isFinite(number) || number <= 0) {
            return 0;
        }
        var rounded = Math.floor(number / 1000) * 1000;
        if (!isFinite(rounded) || rounded < 0) {
            rounded = 0;
        }
        return rounded;
    }

    function formatMoneyInputElement(input) {
        if (!input) return;
        var raw = input.value || '';
        var digits = raw.replace(/\D/g, '');
        if (!digits) {
            input.value = '';
            return;
        }
        var number = parseInt(digits, 10);
        if (!isFinite(number) || number < 0) {
            number = 0;
        }
        input.value = number.toLocaleString('vi-VN');
    }

    function initMoneyInputs() {
        var selector = 'input[data-money-input]';
        var $inputs = $(selector);
        if ($inputs.length) {
            $inputs.each(function () {
                formatMoneyInputElement(this);
            });
        }

        $(document).on('keydown', selector, function (e) {
            var key = e.key;
            var isControl = e.ctrlKey || e.metaKey || e.altKey;
            if (isControl) {
                return;
            }
            if (
                key === 'Backspace' ||
                key === 'Delete' ||
                key === 'Tab' ||
                key === 'Enter' ||
                key === 'ArrowLeft' ||
                key === 'ArrowRight' ||
                key === 'Home' ||
                key === 'End'
            ) {
                return;
            }
            if (key.length === 1 && /[0-9]/.test(key)) {
                return;
            }
            e.preventDefault();
        });

        $(document).on('focus', selector, function () {
            var input = this;
            if (input && input.select) {
                setTimeout(function () {
                    input.select();
                }, 0);
            }
        });

        $(document).on('input', selector, function () {
            formatMoneyInputElement(this);
            if (this.setSelectionRange) {
                var len = this.value.length;
                this.setSelectionRange(len, len);
            }
        });
    }

    var productSelectorModes = {};

    function registerProductSelectorMode(mode, config) {
        if (!mode) return;
        productSelectorModes[mode] = config || {};
    }

    function initProductSelector() {
        var $root = $('[data-product-selector-root]');
        if (!$root.length) return;

        var $search = $root.find('[data-product-selector-search]');
        var $clearSearch = $root.find('[data-product-selector-clear]');
        var $list = $root.find('[data-product-selector-list]');
        var $selected = $root.find('[data-product-selector-selected]');
        var $btnClose = $root.find('[data-product-selector-close]');
        var $btnCancel = $root.find('[data-product-selector-cancel]');
        var $btnConfirm = $root.find('[data-product-selector-confirm]');

        var currentMode = null;
        var currentItems = [];
        var selectedMap = {};
        var currentTrigger = null;
        var filterTimeout = null;
        var filterJobId = 0;

        function applyProductSelectorFilter() {
            var keyword = $.trim($search.val().toString().toLowerCase());
            if ($clearSearch && $clearSearch.length) {
                $clearSearch.toggleClass('hidden', !keyword);
            }

            var items = $list.find('[data-product-selector-item]').get();
            var total = items.length;
            var index = 0;
            var jobId = ++filterJobId;

            function step() {
                if (jobId !== filterJobId) {
                    return;
                }
                var chunk = 80;
                var end = index + chunk;
                if (end > total) {
                    end = total;
                }
                for (var i = index; i < end; i++) {
                    var row = items[i];
                    var text = (row.getAttribute('data-search-text') || '').toString().toLowerCase();
                    var match = !keyword || text.indexOf(keyword) !== -1;
                    if (match) {
                        row.classList.remove('hidden');
                    } else {
                        row.classList.add('hidden');
                    }
                }
                index = end;
                if (index < total) {
                    window.requestAnimationFrame(step);
                }
            }

            window.requestAnimationFrame(step);
        }

        function buildList() {
            $list.empty();
            if (!currentItems || !currentItems.length) {
                return;
            }
            $.each(currentItems, function (index, item) {
                var idStr = String(item.id);
                var searchText = ((item.label || '') + ' ' + (item.sub || '')).trim();
                var classes = 'flex w-full items-center justify-between gap-3 border-b border-slate-100 px-3 py-2 text-left text-sm hover:bg-slate-50';
                if (selectedMap[idStr]) {
                    classes += ' bg-emerald-50';
                }
                var $row = $('<button>', {
                    type: 'button',
                    'data-product-selector-item': '1',
                    'data-item-id': idStr,
                    'data-search-text': searchText,
                    "class": classes
                });

				var $left = $('<div>', { "class": 'flex items-center gap-3 min-w-0' });
				var $thumb = $('<div>', { "class": 'flex h-9 w-9 items-center justify-center overflow-hidden rounded-lg bg-slate-100' });
				if (item.image) {
					var $img = $('<img>', {
						src: item.image,
						alt: item.label || '',
						"class": 'h-full w-full object-cover'
					});
					$thumb.append($img);
				} else {
					$thumb.html('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">\n  <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />\n</svg>');
				}
				$left.append($thumb);

				var $text = $('<div>', { "class": 'min-w-0' });
                var $name = $('<div>', { "class": 'truncate font-medium text-slate-800' }).text(item.label || '');
                $text.append($name);
                if (item.sub) {
                    var $sub = $('<div>', { "class": 'mt-0.5 truncate text-sm text-slate-500' }).text(item.sub);
                    $text.append($sub);
                }
                $left.append($text);

                var $right = $('<div>', { "class": 'flex items-center' });
                var checkClasses = 'inline-flex h-5 w-5 items-center justify-center rounded-full border text-sm';
                if (selectedMap[idStr]) {
                    checkClasses += ' border-emerald-500 bg-emerald-500 text-white';
                } else {
                    checkClasses += ' border-slate-300 text-slate-300';
                }
                var $check = $('<span>', { "class": checkClasses }).html('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3 w-3"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.5a1 1 0 0 1-1.438.012L3.29 9.707a1 1 0 0 1 1.42-1.414l3.017 3.03 6.54-6.764a1 1 0 0 1 1.437.03Z" clip-rule="evenodd" /></svg>');
                $right.append($check);

                $row.append($left).append($right);
                $list.append($row);
            });
            applyProductSelectorFilter();
        }

        function renderSelected() {
            $selected.empty();
            var selectedItems = [];
            if (currentItems && currentItems.length) {
                $.each(currentItems, function (index, item) {
                    var idStr = String(item.id);
                    if (selectedMap[idStr]) {
                        selectedItems.push(item);
                    }
                });
            }
            if (!selectedItems.length) {
                var $empty = $('<div>', { "class": 'text-sm text-slate-500' }).text('Chưa chọn sản phẩm nào.');
                $selected.append($empty);
                return;
            }
            $.each(selectedItems, function (index, item) {
                var $row = $('<div>', { "class": 'mb-1 flex items-center justify-between gap-2 last:mb-0' });
                var $label = $('<div>', { "class": 'text-sm font-medium text-slate-800 truncate' }).text(item.label || '');
                var $remove = $('<button>', {
                    type: 'button',
                    "class": 'ml-2 inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 text-sm text-slate-400 hover:border-rose-400 hover:text-rose-500',
                    'data-product-selector-remove-selected': String(item.id)
                }).html('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3 w-3"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" /></svg>');
                $row.append($label).append($remove);
                $selected.append($row);
            });
        }

        function openModal(mode, trigger) {
            var config = productSelectorModes[mode];
            if (!config || typeof config.buildItems !== 'function') {
                return;
            }
            currentMode = mode;
            currentTrigger = trigger || null;
            selectedMap = {};
            currentItems = config.buildItems() || [];
            $search.val('');
            applyProductSelectorFilter();
            buildList();
            renderSelected();
            $root.removeClass('hidden').addClass('flex');
            $('body').addClass('overflow-hidden');
        }

        function closeModal() {
            currentMode = null;
            currentTrigger = null;
            currentItems = [];
            selectedMap = {};
            $search.val('');
            applyProductSelectorFilter();
            $list.empty();
            $selected.empty();
            $root.addClass('hidden').removeClass('flex');
            $('body').removeClass('overflow-hidden');
        }

        $(document).on('click', '[data-product-selector-open]', function () {
            var $btn = $(this);
            var mode = $btn.attr('data-product-selector-mode') || $btn.data('product-selector-mode');
            if (!mode) return;
            openModal(mode, $btn);
        });

        $btnClose.on('click', function () {
            closeModal();
        });

        $btnCancel.on('click', function () {
            closeModal();
        });

        $btnConfirm.on('click', function () {
            if (!currentMode) {
                closeModal();
                return;
            }
            var config = productSelectorModes[currentMode] || {};
            var items = [];
            if (currentItems && currentItems.length) {
                $.each(currentItems, function (index, item) {
                    var idStr = String(item.id);
                    if (selectedMap[idStr]) {
                        items.push(item);
                    }
                });
            }
            if (!items.length) {
                if (window.showToast) {
                    window.showToast('error', 'Chưa chọn sản phẩm nào.');
                }
                return;
            }
            if (typeof config.onConfirm === 'function') {
                config.onConfirm(items, currentTrigger);
            }
            closeModal();
        });

        $(document).on('keydown', function (e) {
            if ($root.hasClass('hidden')) return;
            if (e.key === 'Escape') {
                e.preventDefault();
                closeModal();
                return;
            }
            if (e.key === 'Enter') {
                var target = e.target;
                if (target && target.tagName === 'TEXTAREA') return;
                e.preventDefault();
                $btnConfirm.trigger('click');
            }
        });

        $search.on('input', function () {
            if (filterTimeout) {
                clearTimeout(filterTimeout);
            }
            filterTimeout = setTimeout(function () {
                applyProductSelectorFilter();
            }, 220);
        });

        if ($clearSearch && $clearSearch.length) {
            $clearSearch.on('click', function (e) {
                e.preventDefault();
                $search.val('');
                applyProductSelectorFilter();
                $search.trigger('focus');
            });
        }

        $(document).on('click', '[data-product-selector-item]', function () {
            var $row = $(this);
            var id = $row.attr('data-item-id');
            if (!id) return;
            if (selectedMap[id]) {
                delete selectedMap[id];
            } else {
                selectedMap[id] = true;
            }
            buildList();
            renderSelected();
        });

        $(document).on('click', '[data-product-selector-remove-selected]', function () {
            var id = $(this).attr('data-product-selector-remove-selected');
            if (!id) return;
            if (selectedMap[id]) {
                delete selectedMap[id];
                buildList();
                renderSelected();
            }
        });
    }

    function initPos() {
        var $root = $('[data-pos-root]');
        if (!$root.length) return;

        var products = window.POS_PRODUCTS || [];
        var productUnits = window.POS_PRODUCT_UNITS || {};

        var $productSearch = $('[data-pos-search]');
        var $productRows = $('[data-pos-product-row]');
        var $cartList = $('[data-pos-cart-list]');
        var $totalEl = $('[data-pos-total]');
        var $form = $('[data-pos-form]');
        var $customerModeButtons = $form.find('[data-pos-customer-mode]');
        var $customerModeInput = $form.find('[data-pos-customer-mode-input]');
        var $existingCustomerWrapper = $form.find('[data-pos-existing-customer-wrapper]');
        var $existingCustomerPlaceholder = $form.find('[data-pos-existing-customer-placeholder]');
        var $existingCustomerName = $form.find('[data-pos-existing-customer-name]');
        var $existingCustomerMeta = $form.find('[data-pos-existing-customer-meta]');
        var $newCustomerFields = $form.find('[data-pos-new-customer]');
        var $customerIdInput = $form.find('input[name="customer_id"][data-pos-customer-id]');
        var $customerModal = $('[data-pos-customer-modal]');
        var $customerSearchInput = $customerModal.find('[data-pos-customer-search]');
        var $customerList = $customerModal.find('[data-pos-customer-list]');
        var $itemsInput = $('[data-pos-items-json]');
		var $submitButton = $form.find('[data-pos-submit-order]');
        var $paymentStatus = $form.find('input[name="payment_status"]');
        var $paymentMethodWrapper = $form.find('[data-pos-payment-method-wrapper]');
        var $paymentAmountInput = $form.find('input[name="payment_amount"][data-money-input]');
        var $subtotalEl = $('[data-pos-subtotal]');
        var $discountDisplay = $('[data-pos-discount-amount]');
        var $discountHidden = $form.find('[data-pos-discount-hidden]');
        var $surchargeDisplay = $('[data-pos-surcharge-amount]');
        var $surchargeHidden = $form.find('[data-pos-surcharge-hidden]');
        var $discountTypeHidden = $form.find('[data-order-discount-type]');
        var $discountValueHidden = $form.find('[data-order-discount-value]');
        var $manualRoot = $root.find('[data-pos-manual-items-root]');
        var $manualRows = $manualRoot.length ? $manualRoot.find('[data-pos-manual-items-rows]') : $();
        var $manualTemplate = $manualRoot.length ? $manualRoot.find('[data-pos-manual-row-template] .pos-manual-item-row').first() : $();
        var manualSummary = {
            totalBuy: 0,
            totalSell: 0,
            itemCount: 0
        };
        window.POS_MANUAL_SUMMARY = manualSummary;
        var currentSubtotal = 0;
        var currentTotal = 0;
        var selectedCustomerId = null;
        var isSubmittingOrder = false;

        if ($productSearch.length) {
            setTimeout(function () {
                $productSearch.trigger('focus');
                var el = $productSearch.get(0);
                if (el && el.select) {
                    el.select();
                }
            }, 10);
        }

        function syncPaymentAmount() {
            if (isSubmittingOrder) return;
            if (!$paymentAmountInput.length || !$paymentStatus.length) return;
            var status = $paymentStatus.filter(':checked').val();
            if (status === 'pay') {
                if (currentTotal > 0) {
                    $paymentAmountInput.val(formatCurrency(currentTotal));
                } else {
                    $paymentAmountInput.val('');
                }
            } else {
                $paymentAmountInput.val('');
            }
        }

        function recalcManualItems() {
            var totalBuy = 0;
            var totalSell = 0;
            var count = 0;
            if ($manualRows.length) {
                $manualRows.find('.pos-manual-item-row').each(function () {
                    var $row = $(this);
                    var qtyRaw = $row.find('input[name="manual_qty[]"]').val() || '';
                    var qty = parseFloat(qtyRaw.toString().replace(',', '.'));
                    if (!isFinite(qty) || qty <= 0) {
                        qty = 0;
                    }
                    var priceBuyRaw = $row.find('input[name="manual_price_buy[]"]').val() || '';
                    var priceSellRaw = $row.find('input[name="manual_price_sell[]"]').val() || '';
                    var priceBuy = parsePurchaseMoney(priceBuyRaw);
                    var priceSell = parsePurchaseMoney(priceSellRaw);
                    var amountBuy = 0;
                    var amountSell = 0;
                    if (qty > 0 && priceBuy > 0) {
                        amountBuy = qty * priceBuy;
                    }
                    if (qty > 0 && priceSell > 0) {
                        amountSell = qty * priceSell;
                    }
                    if (amountBuy > 0) {
                        totalBuy += amountBuy;
                    }
                    if (amountSell > 0) {
                        totalSell += amountSell;
                    }
                    var hasContent = qty > 0 || priceBuy > 0 || priceSell > 0;
                    if (hasContent) {
                        count++;
                    }
                    var $amountDisplay = $row.find('[data-pos-manual-amount-sell]');
                    if ($amountDisplay.length) {
                        if (amountSell > 0) {
                            $amountDisplay.text(formatCurrency(amountSell) + ' đ');
                        } else {
                            $amountDisplay.text('0 đ');
                        }
                    }
                });
            }
            manualSummary = {
                totalBuy: totalBuy,
                totalSell: totalSell,
                itemCount: count
            };
            window.POS_MANUAL_SUMMARY = manualSummary;
            if ($manualRoot.length) {
                var $empty = $manualRoot.find('[data-pos-manual-empty]');
                if ($empty.length) {
                    $empty.toggleClass('hidden', count > 0);
                }
            }
            return manualSummary;
        }

        function recalcCart() {
            var total = 0;
            var hasCartItems = false;
            $cartList.find('[data-pos-cart-item]').each(function () {
                var $row = $(this);
                var price = parseFloat($row.attr('data-pos-price') || '0');
                var qty = parseFloat($row.find('input[data-pos-qty]').val() || '0');
                if (!isFinite(price)) price = 0;
                if (!isFinite(qty)) qty = 0;
                var lineTotal = price * qty;
                total += lineTotal;
                $row.find('[data-pos-line-total]').text(formatCurrency(lineTotal));
                hasCartItems = true;
            });

            var manualTotals = recalcManualItems();
            var manualSell = manualTotals && typeof manualTotals.totalSell !== 'undefined' ? manualTotals.totalSell : 0;
            if (!isFinite(manualSell) || manualSell < 0) {
                manualSell = 0;
            }
            var hasManualItems = manualTotals && typeof manualTotals.itemCount !== 'undefined' && manualTotals.itemCount > 0;
            var hasAnyItems = hasCartItems || hasManualItems;
            var $posEmptyBox = $root.find('[data-pos-empty]').first();
            if ($posEmptyBox.length) {
                $posEmptyBox.toggleClass('hidden', hasAnyItems);
            }
            currentSubtotal = total + manualSell;

            var discountType = 'none';
            var discountValue = 0;
            var discountAmount = 0;
            var surchargeAmount = 0;

            if ($discountTypeHidden.length) {
                discountType = $discountTypeHidden.val() || 'none';
            }
            if ($discountValueHidden.length) {
                discountValue = parseFloat($discountValueHidden.val() || '0');
                if (!isFinite(discountValue) || discountValue < 0) {
                    discountValue = 0;
                }
            }

            if (discountType === 'fixed') {
                discountAmount = discountValue;
            } else if (discountType === 'percent') {
                if (discountValue > 100) {
                    discountValue = 100;
                }
                discountAmount = Math.round(currentSubtotal * discountValue / 100);
            }

            if (discountAmount < 0) {
                discountAmount = 0;
            }
            if (discountAmount > currentSubtotal) {
                discountAmount = currentSubtotal;
            }

            if ($surchargeHidden.length) {
                var surchargeRaw = $surchargeHidden.val() || '0';
                surchargeAmount = parsePurchaseMoney(surchargeRaw);
                if (!isFinite(surchargeAmount) || surchargeAmount < 0) {
                    surchargeAmount = 0;
                }
            }

            var rawTotal = currentSubtotal - discountAmount + surchargeAmount;
            if (!isFinite(rawTotal) || rawTotal < 0) {
                rawTotal = 0;
            }

            var displayTotal = roundDownThousand(rawTotal);
            currentTotal = displayTotal;

            if ($subtotalEl.length) {
                $subtotalEl.text(formatCurrency(currentSubtotal) + ' đ');
            }
            if ($discountDisplay.length) {
                $discountDisplay.text('-' + formatCurrency(discountAmount) + ' đ');
            }
            if ($surchargeDisplay.length) {
                $surchargeDisplay.text('+' + formatCurrency(surchargeAmount) + ' đ');
            }
            if ($totalEl.length) {
                $totalEl.text(formatCurrency(displayTotal) + ' đ');
            }
            if ($discountHidden.length) {
                $discountHidden.val(discountAmount);
            }
            syncPaymentAmount();
        }

        function setCustomerMode(mode) {
            if (!$customerModeInput.length) return;
            if (mode !== 'existing' && mode !== 'new' && mode !== 'guest') {
                mode = 'guest';
            }
            $customerModeInput.val(mode);
            if ($customerModeButtons.length) {
                $customerModeButtons.each(function () {
                    var $btn = $(this);
                    var btnMode = $btn.attr('data-pos-customer-mode') || '';
                    var isActive = btnMode === mode;
                    $btn.toggleClass('bg-emerald-600 text-white shadow-sm', isActive);
                    $btn.toggleClass('bg-slate-100 text-slate-700', !isActive);
                });
            }
            var showExisting = mode === 'existing';
            var showNew = mode === 'new';
            if ($existingCustomerWrapper.length) {
                $existingCustomerWrapper.toggleClass('hidden', !showExisting);
            }
            if ($newCustomerFields.length) {
                $newCustomerFields.toggleClass('hidden', !showNew);
            }
            if (mode !== 'existing') {
                selectedCustomerId = null;
                if ($customerIdInput.length) {
                    $customerIdInput.val('');
                }
                if ($existingCustomerName.length) {
                    $existingCustomerName.text('Chưa chọn khách');
                }
                if ($existingCustomerMeta.length) {
                    $existingCustomerMeta.text('Nhấn để chọn khách từ danh sách');
                }
            }
        }

        function highlightSelectedCustomer() {
            if (!$customerList.length) return;
            $customerList.find('[data-pos-customer-item]').each(function () {
                var $item = $(this);
                var id = String($item.attr('data-customer-id') || '');
                var active = selectedCustomerId != null && String(selectedCustomerId) === id;
                $item.toggleClass('bg-emerald-50', active);
                var $indicator = $item.find('[data-pos-customer-selected-indicator]');
                if ($indicator.length) {
                    $indicator.toggleClass('hidden', !active);
                }
            });
        }

        function filterCustomerList(keyword) {
            if (!$customerList.length) return;
            var q = (keyword || '').toString().toLowerCase().trim();
            $customerList.find('[data-pos-customer-item]').each(function () {
                var $item = $(this);
                var text = ($item.attr('data-search-text') || $item.text() || '').toLowerCase();
                var match = !q || text.indexOf(q) !== -1;
                $item.toggleClass('hidden', !match);
            });
        }

        function openCustomerModal() {
            if (!$customerModal.length) return;
            $customerModal.removeClass('hidden').addClass('flex');
            if ($customerSearchInput.length) {
                $customerSearchInput.val('');
            }
            filterCustomerList('');
            selectedCustomerId = null;
            var currentMode = $customerModeInput.length ? ($customerModeInput.val() || 'guest') : 'guest';
            if (currentMode === 'existing' && $customerIdInput.length) {
                var rawId = $.trim($customerIdInput.val() || '');
                var parsedId = parseInt(rawId, 10);
                if (parsedId && parsedId > 0) {
                    selectedCustomerId = String(parsedId);
                }
            }
            highlightSelectedCustomer();
        }

        function closeCustomerModal() {
            if (!$customerModal.length) return;
            $customerModal.addClass('hidden').removeClass('flex');
        }

        function applySelectedCustomer() {
            if (!selectedCustomerId || !$customerIdInput.length) {
                closeCustomerModal();
                return;
            }
            $customerIdInput.val(String(selectedCustomerId));
            if ($existingCustomerName.length && $existingCustomerMeta.length) {
                var selector = '[data-pos-customer-item][data-customer-id="' + String(selectedCustomerId) + '"]';
                var $item = $customerList.find(selector).first();
                if ($item.length) {
                    var name = $item.attr('data-customer-name') || '';
                    var phone = $item.attr('data-customer-phone') || '';
                    var address = $item.attr('data-customer-address') || '';
                    var line1 = name;
                    if (phone) {
                        line1 += ' - ' + phone;
                    }
                    $existingCustomerName.text(line1 || 'Chưa chọn khách');
                    $existingCustomerMeta.text(address || '');
                }
            }
            closeCustomerModal();
        }

        if ($customerModeButtons.length && $customerModeInput.length) {
            $customerModeButtons.on('click', function () {
                var mode = $(this).attr('data-pos-customer-mode') || 'guest';
                setCustomerMode(mode);
            });
            var initialMode = $customerModeInput.val() || 'guest';
            setCustomerMode(initialMode);
        }

        if ($existingCustomerPlaceholder.length && $customerModal.length) {
            $existingCustomerPlaceholder.on('click', function () {
                openCustomerModal();
            });
        }

        if ($customerList.length) {
            $customerList.on('click', '[data-pos-customer-item]', function () {
                var $item = $(this);
                selectedCustomerId = $item.attr('data-customer-id') || null;
                highlightSelectedCustomer();
            });
        }

        if ($customerSearchInput.length) {
            $customerSearchInput.on('input', function () {
                filterCustomerList($(this).val() || '');
            });
        }

        if ($customerModal.length) {
            $customerModal.on('click', function (e) {
                if (e.target === this) {
                    closeCustomerModal();
                }
            });
            $customerModal.find('[data-pos-customer-cancel]').on('click', function () {
                closeCustomerModal();
            });
            $customerModal.find('[data-pos-customer-confirm]').on('click', function () {
                applySelectedCustomer();
            });
        }

        function togglePaymentMethod() {
            if (!$paymentStatus.length || !$paymentMethodWrapper.length) return;
            var current = $paymentStatus.filter(':checked').val();
            $paymentMethodWrapper.toggleClass('hidden', current !== 'pay');
            syncPaymentAmount();
        }

        if ($paymentStatus.length && $paymentMethodWrapper.length) {
            $paymentStatus.on('change', function () {
                togglePaymentMethod();
            });
            togglePaymentMethod();
        }

        $form.on('discount:change', function () {
            isSubmittingOrder = true;
            recalcCart();
            isSubmittingOrder = false;
        });

        $root.on('click', '[data-order-total-round]', function () {
            recalcCart();
        });

		function addToCart(productId) {
            var units = productUnits[productId] || [];
            if (!units.length) {
                if (window.showToast) {
                    window.showToast('error', 'Sản phẩm chưa cấu hình đơn vị bán.');
                }
                return;
            }

            var unit = units[0];
            var $existing = $cartList.find('[data-pos-cart-item][data-product-id="' + productId + '"][data-unit-id="' + unit.unit_id + '"]');
            if ($existing.length) {
                var $qtyInput = $existing.find('input[data-pos-qty]');
                var currentQty = parseFloat($qtyInput.val() || '0');
                if (!isFinite(currentQty)) currentQty = 0;
                var stepExisting = parseFloat($qtyInput.attr('step') || '1');
                if (!isFinite(stepExisting) || stepExisting <= 0) {
                    stepExisting = 1;
                }
                $qtyInput.val((currentQty + stepExisting).toFixed(4).replace(/\.?0+$/, '')).trigger('change');
                recalcCart();
                return;
            }

            var product = null;
            $.each(products, function (index, p) {
                if (String(p.id) === String(productId)) {
                    product = p;
                    return false;
                }
            });
            if (!product) return;

            var imgSrc = product.image_path ? (window.APP_BASE_PATH + '/' + product.image_path) : '';
            var basePrice = unit.price_sell || 0;

			var $row = $('<div>', {
				'data-pos-cart-item': '1',
				'data-product-id': productId,
				'data-unit-id': unit.unit_id,
				'data-pos-price': basePrice,
				'data-pos-base-price': basePrice,
				"class": 'flex items-center justify-between gap-3 py-2 border-b border-slate-200 last:border-b-0'
			});
		
			var $left = $('<div>', { "class": 'flex items-center gap-3' });
			var $thumb = $('<div>', { "class": 'flex h-12 w-12 items-center justify-center overflow-hidden rounded-xl bg-slate-100 text-sm font-medium text-slate-400' });
			if (imgSrc) {
				var $img = $('<img>', {
					src: imgSrc,
					alt: product.name || '',
					"class": 'h-full w-full object-cover'
				});
				$thumb.empty().append($img);
			} else {
				$thumb.html(`<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
		  <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
</svg>`);
			}

			var $info = $('<div>');
			var $productText = $('<div>', { "class": 'text-sm font-medium text-slate-900' }).text(product.name);
			var $productSub = $('<div>', { "class": 'text-sm text-slate-500' }).text(product.code);
            var priceDisplay = basePrice > 0 ? formatCurrency(basePrice) + ' đ' : '0 đ';
            var $unitInfo = $('<div>', { "class": 'mt-0.5 flex items-center gap-1 text-sm text-slate-500' });
            var $priceDisplay = $('<button>', {
                type: 'button',
                'data-pos-price-edit': '1',
                "class": 'inline-flex items-center gap-1 rounded-full px-2 py-0.5 hover:bg-emerald-50'
            });
            var $priceText = $('<span>', {
                "class": 'font-medium text-slate-900',
                'data-pos-price-display': '1'
            }).text(priceDisplay);
            var unitNameText = unit.unit_name ? ('/ ' + unit.unit_name) : '';
            var $unitName = $('<span>').text(unitNameText);
            var $editIcon = $('<span>', {
                "class": 'inline-flex h-4 w-4 items-center justify-center text-slate-400 group-hover:text-emerald-600'
            }).html('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3 w-3"><path d="M13.586 3.586a2 2 0 0 1 2.828 2.828l-.793.793-2.828-2.828.793-.793ZM11.379 5.793 4 13.172V16h2.828l7.38-7.379-2.83-2.828Z" /></svg>');
            $priceDisplay.append($priceText).append($unitName).append($editIcon);
            $unitInfo.append($priceDisplay);
		
			var $qtyGroup = $('<div>', { "class": 'inline-flex items-stretch overflow-hidden rounded-full border border-slate-300 bg-slate-50' });
		
			var $decreaseBtn = $('<button>', {
				type: 'button',
				'data-pos-decrease': '1',
				"class": 'inline-flex h-6 w-6 items-center justify-center bg-slate-50 text-sm text-slate-700 hover:bg-slate-100'
			}).html(`<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3">
  <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
</svg>`);
            var min = 1;
            var step = 1;
            if (unit.allow_fraction) {
                step = unit.min_step && unit.min_step > 0 ? unit.min_step : 0.1;
                min = step;
            }
			var $qtyInput = $('<input>', {
				type: 'number',
				min: String(min),
				step: String(step),
				value: String(min),
				'data-pos-qty': '1',
				"class": 'h-6 w-10 border-0 bg-slate-50 px-1 text-sm font-medium text-center outline-none'
			});
			var $increaseBtn = $('<button>', {
				type: 'button',
				'data-pos-increase': '1',
				"class": 'inline-flex h-6 w-6 items-center justify-center bg-slate-50 text-sm text-slate-700 hover:bg-slate-100'
			}).html(`<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3">
  <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
</svg>`);

			$qtyGroup.append($decreaseBtn).append($qtyInput).append($increaseBtn);
		
            var $qtyWrapper = $('<div>', { "class": 'mt-1' }).append($qtyGroup);
			$info.append($productText).append($unitInfo).append($qtyWrapper);
			$left.append($thumb).append($info);
		
			var $right = $('<div>', { "class": 'flex flex-col items-end justify-between gap-2 flex-1' });
			var $topRow = $('<div>', { "class": 'flex w-full justify-end' });
		
			var $line = $('<div>', { "class": 'text-sm font-medium text-emerald-600' });
			var $lineTotal = $('<span>', { 'data-pos-line-total': '1' }).text(formatCurrency(basePrice));
			$line.append($lineTotal).append(' đ');
		
			var $removeBtn = $('<button>', {
				type: 'button',
				'data-pos-remove': '1',
				"class": 'inline-flex h-5 w-5 items-center justify-center text-rose-500 hover:text-rose-600'
			}).html(`<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
</svg>`);
		
			$topRow.append($removeBtn);
			$right.append($topRow).append($line);
		
			$row.append($left).append($right);
            $cartList.append($row);

            recalcCart();
        }

        $productSearch.on('input', function () {
            var keyword = $(this).val().toString().toLowerCase();
            $productRows.each(function () {
                var $row = $(this);
                var name = ($row.data('product-name') || '').toString().toLowerCase();
                var code = ($row.data('product-code') || '').toString().toLowerCase();
                var match = !keyword || name.indexOf(keyword) !== -1 || code.indexOf(keyword) !== -1;
                $row.toggleClass('hidden', !match);
            });
        });

        $(document).on('click', '[data-pos-add]', function () {
            var $row = $(this).closest('[data-pos-product-row]');
            if (!$row.length) return;
            var productId = $row.data('product-id');
            if (!productId) return;
            addToCart(productId);
        });

        $(document).on('focus', 'input[data-pos-qty]', function () {
            var input = this;
            if (input && input.select) {
                setTimeout(function () {
                    input.select();
                }, 0);
            }
        });

        $(document).on('change', 'input[data-pos-qty]', function () {
            var $input = $(this);
            var step = parseFloat($input.attr('step') || '1');
            var min = parseFloat($input.attr('min') || '1');
            var value = parseFloat($input.val() || '0');
            if (!isFinite(step) || step <= 0) {
                step = 1;
            }
            if (!isFinite(min) || min <= 0) {
                min = step;
            }
            if (!isFinite(value) || value <= 0) {
                value = min;
            }
            var steps = Math.round(value / step);
            value = steps * step;
            if (value < min) {
                value = min;
            }
            $input.val(value.toFixed(4).replace(/\.?0+$/, ''));
            recalcCart();
        });

        $(document).on('click', '[data-pos-remove]', function () {
            var $row = $(this).closest('[data-pos-cart-item]');
            if (!$row.length) return;
            $row.remove();
            recalcCart();
        });

        var $manualEditModal = $('[data-pos-manual-edit-modal]');
        var currentManualRow = null;

        function openManualEditModal($row) {
            if (!$manualEditModal.length) return;
            currentManualRow = $row;
            if (!currentManualRow || !currentManualRow.length) return;

            var name = currentManualRow.find('input[name="manual_item_name[]"]').val() || '';
            var unit = currentManualRow.find('input[name="manual_unit_name[]"]').val() || '';
            var qty = currentManualRow.find('input[name="manual_qty[]"]').val() || '';
            var priceBuy = currentManualRow.find('input[name="manual_price_buy[]"]').val() || '';
            var priceSell = currentManualRow.find('input[name="manual_price_sell[]"]').val() || '';

            var $editName = $manualEditModal.find('[data-pos-manual-edit-name]');
            var $editUnit = $manualEditModal.find('[data-pos-manual-edit-unit]');
            var $editQty = $manualEditModal.find('[data-pos-manual-edit-qty]');
            var $editPriceBuy = $manualEditModal.find('[data-pos-manual-edit-price-buy]');
            var $editAmountBuy = $manualEditModal.find('[data-pos-manual-edit-amount-buy]');
            var $editPriceSell = $manualEditModal.find('[data-pos-manual-edit-price-sell]');

            $editName.val(name);
            $editUnit.val(unit);
            $editQty.val(qty);
            $editPriceBuy.val(priceBuy);
            $editPriceSell.val(priceSell);

            if ($editAmountBuy.length) {
                var qtyNum = parseFloat((qty || '').toString().replace(',', '.'));
                if (!isFinite(qtyNum) || qtyNum <= 0) {
                    qtyNum = 0;
                }
                var priceBuyNum = parsePurchaseMoney(priceBuy);
                var amountBuy = 0;
                if (qtyNum > 0 && priceBuyNum > 0) {
                    amountBuy = qtyNum * priceBuyNum;
                }
                if (amountBuy > 0) {
                    $editAmountBuy.val(formatCurrency(amountBuy));
                } else {
                    $editAmountBuy.val('');
                }
            }

            $manualEditModal.removeClass('hidden').addClass('flex');
        }

        function closeManualEditModal() {
            if (!$manualEditModal.length) return;
            $manualEditModal.addClass('hidden').removeClass('flex');
            currentManualRow = null;
        }

        $root.on('click', '[data-pos-manual-add-row]', function (e) {
            e.preventDefault();
            var $row;
            if ($manualTemplate.length) {
                $row = $manualTemplate.clone(true);
            } else if ($manualRows.length) {
                $row = $manualRows.find('.pos-manual-item-row').first().clone(true);
            }
            if (!$row || !$row.length || !$manualRows.length) return;
            $row.find('input[name="manual_item_name[]"]').val('');
            $row.find('input[name="manual_unit_name[]"]').val('');
            $row.find('input[name="manual_qty[]"]').val('');
            $row.find('input[name="manual_price_buy[]"]').val('');
            $row.find('input[name="manual_price_sell[]"]').val('');
            $row.find('[data-pos-manual-display-name]').text('Chưa nhập tên hàng');
            $row.find('[data-pos-manual-display-unit]').text('');
            $row.find('[data-pos-manual-display-qty]').text('0');
            $row.find('[data-pos-manual-display-buy]').text('0 đ');
            $row.find('[data-pos-manual-display-sell]').text('0 đ');
            $row.find('[data-pos-manual-line-buy-total]').text('0 đ');
            $row.find('[data-pos-manual-line-sell-total]').text('0 đ');
            $manualRows.append($row);
            openManualEditModal($row);
        });

        $root.on('click', '.pos-manual-item-row', function (e) {
            if ($(e.target).closest('[data-pos-manual-remove-row]').length) {
                return;
            }
            e.preventDefault();
            openManualEditModal($(this));
        });

        $root.on('click', '[data-pos-manual-remove-row]', function (e) {
            e.preventDefault();
            var $row = $(this).closest('.pos-manual-item-row');
            if ($row.length) {
                $row.remove();
                recalcCart();
            }
        });

        if ($manualEditModal.length) {
            var $editName = $manualEditModal.find('[data-pos-manual-edit-name]');
            var $editUnit = $manualEditModal.find('[data-pos-manual-edit-unit]');
            var $editQty = $manualEditModal.find('[data-pos-manual-edit-qty]');
            var $editPriceBuy = $manualEditModal.find('[data-pos-manual-edit-price-buy]');
            var $editAmountBuy = $manualEditModal.find('[data-pos-manual-edit-amount-buy]');
            var $editPriceSell = $manualEditModal.find('[data-pos-manual-edit-price-sell]');

            var updateManualAmountFromPrice = function () {
                var qtyVal = $editQty.val() || '';
                var priceVal = $editPriceBuy.val() || '';
                var qtyNum = parseFloat(qtyVal.toString().replace(',', '.'));
                if (!isFinite(qtyNum) || qtyNum <= 0) {
                    qtyNum = 0;
                }
                var priceNum = parsePurchaseMoney(priceVal);
                var amount = 0;
                if (qtyNum > 0 && priceNum > 0) {
                    amount = qtyNum * priceNum;
                }
                if ($editAmountBuy.length) {
                    if (amount > 0) {
                        $editAmountBuy.val(formatCurrency(amount));
                    } else {
                        $editAmountBuy.val('');
                    }
                }
            };

            var updateManualPriceFromAmount = function () {
                if (!$editAmountBuy.length) return;
                var qtyVal = $editQty.val() || '';
                var amountVal = $editAmountBuy.val() || '';
                var qtyNum = parseFloat(qtyVal.toString().replace(',', '.'));
                if (!isFinite(qtyNum) || qtyNum <= 0) {
                    return;
                }
                var amountNum = parsePurchaseMoney(amountVal);
                if (!isFinite(amountNum) || amountNum <= 0) {
                    return;
                }
                var newPrice = Math.round(amountNum / qtyNum);
                if (isFinite(newPrice) && newPrice > 0) {
                    $editPriceBuy.val(formatCurrency(newPrice));
                }
            };

            $editQty.on('input', updateManualAmountFromPrice);
            $editPriceBuy.on('input', updateManualAmountFromPrice);
            if ($editAmountBuy.length) {
                $editAmountBuy.on('input', updateManualPriceFromAmount);
            }

            $(document).on('click', '[data-pos-manual-edit-cancel]', function (e) {
                e.preventDefault();
                closeManualEditModal();
            });

            $(document).on('click', '[data-pos-manual-edit-save]', function (e) {
                e.preventDefault();
                if (!currentManualRow || !currentManualRow.length) {
                    closeManualEditModal();
                    return;
                }

                var nameVal = $editName.val() || '';
                var unitVal = $editUnit.val() || '';
                var qtyVal = $editQty.val() || '';
                var priceBuyVal = $editPriceBuy.val() || '';
                var amountBuyVal = $editAmountBuy.length ? $editAmountBuy.val() || '' : '';
                var priceSellVal = $editPriceSell.val() || '';

                var qtyNum = parseFloat(qtyVal.toString().replace(',', '.'));
                if (!isFinite(qtyNum) || qtyNum <= 0) {
                    qtyNum = 0;
                }
                var amountBuyNum = parsePurchaseMoney(amountBuyVal);
                var priceBuyNum = parsePurchaseMoney(priceBuyVal);

                if (qtyNum > 0 && amountBuyNum > 0) {
                    var newPriceBuy = Math.round(amountBuyNum / qtyNum);
                    if (isFinite(newPriceBuy) && newPriceBuy > 0) {
                        priceBuyNum = newPriceBuy;
                        priceBuyVal = formatCurrency(priceBuyNum);
                    }
                } else if (priceBuyNum > 0 && qtyNum > 0 && $editAmountBuy.length) {
                    var recalculatedAmount = priceBuyNum * qtyNum;
                    $editAmountBuy.val(formatCurrency(recalculatedAmount));
                }

                currentManualRow.find('input[name="manual_item_name[]"]').val(nameVal);
                currentManualRow.find('input[name="manual_unit_name[]"]').val(unitVal);
                currentManualRow.find('input[name="manual_qty[]"]').val(qtyVal);
                currentManualRow.find('input[name="manual_price_buy[]"]').val(priceBuyVal);
                currentManualRow.find('input[name="manual_price_sell[]"]').val(priceSellVal);

                var displayQty = qtyVal || '0';
                var displayBuy = priceBuyVal && priceBuyNum > 0 ? formatCurrency(priceBuyNum) + ' đ' : '0 đ';
                var priceSellNum = parsePurchaseMoney(priceSellVal);
                var displaySell = priceSellNum > 0 ? formatCurrency(priceSellNum) + ' đ' : '0 đ';
                var lineBuyTotal = 0;
                var lineSellTotal = 0;
                if (qtyNum > 0 && priceBuyNum > 0) {
                    lineBuyTotal = qtyNum * priceBuyNum;
                }
                if (qtyNum > 0 && priceSellNum > 0) {
                    lineSellTotal = qtyNum * priceSellNum;
                }

                currentManualRow.find('[data-pos-manual-display-name]').text(nameVal || 'Chưa nhập tên hàng');
                currentManualRow.find('[data-pos-manual-display-unit]').text(unitVal ? ' - ' + unitVal : '');
                currentManualRow.find('[data-pos-manual-display-qty]').text(displayQty);
                currentManualRow.find('[data-pos-manual-display-buy]').text(displayBuy);
                currentManualRow.find('[data-pos-manual-display-sell]').text(displaySell);
                currentManualRow.find('[data-pos-manual-line-buy-total]').text(lineBuyTotal > 0 ? formatCurrency(lineBuyTotal) + ' đ' : '0 đ');
                currentManualRow.find('[data-pos-manual-line-sell-total]').text(lineSellTotal > 0 ? formatCurrency(lineSellTotal) + ' đ' : '0 đ');

                closeManualEditModal();
                recalcCart();
            });

            $manualEditModal.on('click', function (e) {
                if (e.target !== this) return;
                closeManualEditModal();
            });

            $(document).on('keydown', function (e) {
                if ($manualEditModal.hasClass('hidden')) return;
                if (e.key === 'Escape') {
                    e.preventDefault();
                    closeManualEditModal();
                }
            });
        }

        $(document).on('click', '[data-pos-price-edit]', function () {
            var $btn = $(this);
            var $row = $btn.closest('[data-pos-cart-item]');
            if (!$row.length) return;
            var $display = $row.find('[data-pos-price-display]');
            if (!$display.length) return;
            var $modal = $('[data-pos-price-modal]');
            if (!$modal.length) return;
            var basePriceRaw = $row.attr('data-pos-base-price') || '0';
            var basePrice = parseFloat(basePriceRaw);
            if (!isFinite(basePrice) || basePrice < 0) {
                basePrice = 0;
            }
            var currentPriceRaw = $row.attr('data-pos-price') || basePriceRaw;
            var currentPrice = parseFloat(currentPriceRaw);
            if (!isFinite(currentPrice) || currentPrice < 0) {
                currentPrice = basePrice;
            }
            var productName = $row.find('.text-sm.font-medium.text-slate-900').first().text() || '';
            $modal.data('row', $row);
            $modal.data('basePrice', basePrice);
            $modal.data('currentPrice', currentPrice);
            $modal.data('context', 'pos');
            $modal.find('[data-pos-price-modal-product]').text(productName);
            $modal.find('[data-pos-price-modal-base]').text(formatCurrency(basePrice) + ' đ');
            $modal.find('[data-pos-price-modal-current]').text(formatCurrency(currentPrice) + ' đ');
            var $input = $modal.find('[data-pos-price-modal-input]');
            if ($input.length) {
                $input.val(currentPrice > 0 ? formatCurrency(currentPrice) : '0');
            }
            $modal.removeClass('hidden');
        });

        $(document).on('click', '[data-pos-price-cancel]', function () {
            var $modal = $('[data-pos-price-modal]');
            if (!$modal.length) return;
            $modal.addClass('hidden');
            $modal.removeData('row').removeData('basePrice').removeData('currentPrice').removeData('context');
        });
        
        $(document).on('click', '[data-pos-price-save]', function () {
            var $modal = $('[data-pos-price-modal]');
            if (!$modal.length) return;
            var $row = $modal.data('row');
            if (!$row || !$row.length) {
                $modal.addClass('hidden');
                return;
            }
            var basePrice = parseFloat($modal.data('basePrice') || '0');
            if (!isFinite(basePrice) || basePrice < 0) {
                basePrice = 0;
            }
            var $input = $modal.find('[data-pos-price-modal-input]');
            if (!$input.length) return;
            var raw = $input.val() != null ? $input.val().toString() : '';
            var numeric = raw.replace(/[^\d]/g, '');
            var newPrice = parseFloat(numeric || '0');
            if (!isFinite(newPrice) || newPrice <= 0) {
                newPrice = basePrice;
            }
            if (!isFinite(newPrice) || newPrice < 0) {
                newPrice = 0;
            }
            var price = newPrice;
            var $display = $row.find('[data-pos-price-display]');
            $row.attr('data-pos-price', price);
            var displayText = price > 0 ? (formatCurrency(price) + ' đ') : '0 đ';
            $display.text(displayText);
            $display.removeClass('text-amber-700');
            $display.removeClass('text-slate-900');
            if (basePrice > 0 && price < basePrice - 0.0001) {
                $display.addClass('text-amber-700');
            } else {
                $display.addClass('text-slate-900');
            }
            recalcCart();
            $modal.addClass('hidden');
            $modal.removeData('row').removeData('basePrice').removeData('currentPrice').removeData('context');
        });

        $(document).on('click', '[data-pos-increase]', function () {
            var $row = $(this).closest('[data-pos-cart-item]');
            if (!$row.length) return;
            var $input = $row.find('input[data-pos-qty]');
            if (!$input.length) return;
            var step = parseFloat($input.attr('step') || '1');
            var value = parseFloat($input.val() || '0');
            if (!isFinite(step) || step <= 0) {
                step = 1;
            }
            if (!isFinite(value) || value < 0) {
                value = 0;
            }
            $input.val((value + step).toFixed(4).replace(/\.?0+$/, '')).trigger('change');
        });

        $(document).on('click', '[data-pos-decrease]', function () {
            var $row = $(this).closest('[data-pos-cart-item]');
            if (!$row.length) return;
            var $input = $row.find('input[data-pos-qty]');
            if (!$input.length) return;
            var step = parseFloat($input.attr('step') || '1');
            var min = parseFloat($input.attr('min') || '1');
            var value = parseFloat($input.val() || '0');
            if (!isFinite(step) || step <= 0) {
                step = 1;
            }
            if (!isFinite(min) || min <= 0) {
                min = step;
            }
            if (!isFinite(value) || value <= min) {
                value = min;
            } else {
                value = value - step;
                if (value < min) {
                    value = min;
                }
            }
            $input.val(value.toFixed(4).replace(/\.?0+$/, '')).trigger('change');
        });

        $(document).on('click', '[data-pos-submit-order]', function (e) {
            e.preventDefault();
            if (!$form.length || !$itemsInput.length) return;

            var customerMode = $customerModeInput.length ? ($customerModeInput.val() || 'guest') : 'guest';
            if (customerMode === 'existing') {
                var currentCustomerId = $customerIdInput.length ? ($customerIdInput.val() || '') : '';
                if (!currentCustomerId) {
                    if (window.showToast) {
                        window.showToast('error', 'Vui lòng chọn khách hàng cũ trước khi lưu đơn.');
                    }
                    if ($existingCustomerPlaceholder.length) {
                        $existingCustomerPlaceholder.trigger('click');
                    }
                    return;
                }
            } else if (customerMode === 'new') {
                var $customerNameInput = $form.find('input[name="customer_name"]');
                var customerNameVal = $.trim($customerNameInput.val() || '');
                if (!customerNameVal) {
                    if (window.showToast) {
                        window.showToast('error', 'Vui lòng nhập tên khách hàng mới.');
                    }
                    $customerNameInput.trigger('focus');
                    return;
                }
            }

            var items = [];
            $cartList.find('[data-pos-cart-item]').each(function () {
                var $row = $(this);
                var productId = parseInt($row.data('product-id') || '0', 10);
                var unitId = parseInt($row.attr('data-unit-id') || '0', 10);
                var price = parseFloat($row.attr('data-pos-price') || '0');
                var qty = parseFloat($row.find('input[data-pos-qty]').val() || '0');
                if (!productId || !unitId || !qty || !price) {
                    return;
                }
                items.push({
                    product_id: productId,
                    unit_id: unitId,
                    quantity: qty,
                    price: price
                });
            });

            isSubmittingOrder = true;
            recalcCart();
            isSubmittingOrder = false;

            var manualCount = 0;
            if ($manualRows.length) {
                $manualRows.find('.pos-manual-item-row').each(function () {
                    var $row = $(this);
                    var qtyRaw = $row.find('input[name="manual_qty[]"]').val() || '';
                    var qty = parseFloat(qtyRaw.toString().replace(',', '.'));
                    if (!isFinite(qty) || qty <= 0) {
                        qty = 0;
                    }
                    var priceSellRaw = $row.find('input[name="manual_price_sell[]"]').val() || '';
                    var priceSell = parsePurchaseMoney(priceSellRaw);
                    if (!isFinite(priceSell) || priceSell < 0) {
                        priceSell = 0;
                    }
                    if (qty > 0 && priceSell > 0) {
                        manualCount++;
                    }
                });
            }
            if (!items.length && !manualCount) {
                if (window.showToast) {
                    window.showToast('error', 'Giỏ hàng đang trống.');
                }
                return;
            }

            $itemsInput.val(JSON.stringify(items));
            $form.trigger('submit');
        });

		$(document).on('keydown', function (e) {
			if (!$root.length) return;

            var $selectorRoot = $('[data-product-selector-root]');
            if ($selectorRoot.length && !$selectorRoot.hasClass('hidden')) {
                return;
            }

            if (e.key === 'F2') {
                if ($productSearch.length) {
                    e.preventDefault();
                    $productSearch.trigger('focus');
                    var searchEl = $productSearch.get(0);
                    if (searchEl && searchEl.select) {
                        searchEl.select();
                    }
                }
                return;
            }

            if (e.key === 'F3') {
                if ($paymentAmountInput.length) {
                    e.preventDefault();
                    $paymentAmountInput.trigger('focus');
                    var payEl = $paymentAmountInput.get(0);
                    if (payEl && payEl.select) {
                        payEl.select();
                    }
                }
                return;
            }

			if (e.key === 'Enter') {
				var target = e.target;
				if (target && (target.tagName === 'TEXTAREA')) return;
				if ($submitButton.length) {
					e.preventDefault();
					$submitButton.trigger('click');
				}
			}
		});

		registerProductSelectorMode('pos', {
			buildItems: function () {
				var items = [];
				$.each(products, function (index, p) {
					var units = productUnits[p.id] || [];
					var primaryUnit = units.length ? units[0] : null;
					var unitName = primaryUnit && primaryUnit.unit_name ? primaryUnit.unit_name : (p.base_unit_name || '');
					var priceSell = primaryUnit && primaryUnit.price_sell ? primaryUnit.price_sell : 0;
					var priceText = priceSell ? formatCurrency(priceSell) + ' đ' : '';
					var label = unitName ? (p.name + ' - ' + unitName) : p.name;
					items.push({
						id: p.id,
						label: label,
						sub: priceText,
						image: p.image_url || null
					});
				});
				return items;
			},
            onConfirm: function (items) {
                if (!items || !items.length) return;
                $.each(items, function (index, item) {
                    if (!item || !item.id) return;
                    addToCart(item.id);
                });
            }
        });

        recalcCart();
    }

    function initOrderAdd() {
        var $root = $('[data-order-add-root]');
        if (!$root.length) return;

        var $rowsContainer = $root.find('[data-order-add-rows]');
        if (!$rowsContainer.length) return;

        function getOrCreateRow(index) {
            var $rows = $rowsContainer.find('[data-order-add-row]');
            if (!$rows.length) return null;
            if (index < $rows.length) {
                return $rows.eq(index);
            }
            var $clone = $rows.eq(0).clone(true);
            $clone.find('select[name="product_unit_id[]"]').val('');
            $clone.find('input[name="qty[]"]').val('0');
            $rowsContainer.append($clone);
            return $clone;
        }

		registerProductSelectorMode('order-add', {
			buildItems: function () {
				var source = window.ORDER_ADD_PRODUCT_UNITS || [];
				var items = [];
				$.each(source, function (index, row) {
					items.push({
						id: row.id,
						label: row.product_name + ' - ' + row.unit_name,
						sub: row.price_text || '',
						image: row.image_url || null
					});
				});
				return items;
			},
            onConfirm: function (items) {
                if (!items || !items.length) return;
                var index = 0;
                $rowsContainer.find('[data-order-add-row]').each(function () {
                    if (index >= items.length) {
                        return false;
                    }
                    var $row = $(this);
                    var $select = $row.find('select[name="product_unit_id[]"]');
                    var $qty = $row.find('input[name="qty[]"]');
                    if (!$select.length || !$qty.length) return;
                    if ($select.val()) return;
                    var item = items[index];
                    $select.val(item.id);
                    if (!$qty.val() || $qty.val() === '0') {
                        $qty.val('1');
                    }
                    index++;
                });
                while (index < items.length) {
                    var extra = items[index];
                    var $newRow = getOrCreateRow(index);
                    if (!$newRow || !extra) {
                        index++;
                        continue;
                    }
                    var $select2 = $newRow.find('select[name="product_unit_id[]"]');
                    var $qty2 = $newRow.find('input[name="qty[]"]');
                    if ($select2.length && $qty2.length) {
                        $select2.val(extra.id);
                        if (!$qty2.val() || $qty2.val() === '0') {
                            $qty2.val('1');
                        }
                    }
                    index++;
                }
            }
        });
    }

    function initOrderReturn() {
        var $root = $('[data-order-return-root]');
        if (!$root.length) return;

        registerProductSelectorMode('order-return', {
            buildItems: function () {
                var source = window.ORDER_RETURN_ITEMS || [];
                var items = [];
                $.each(source, function (index, row) {
                    items.push({
                        id: row.id,
                        label: row.product_name + ' - ' + row.unit_name,
                        sub: row.qty_text || ''
                    });
                });
                return items;
            },
            onConfirm: function (items) {
                if (!items || !items.length) return;
                var selected = {};
                $.each(items, function (index, item) {
                    selected[String(item.id)] = item;
                });
                $root.find('input[name^="return_qty["]').each(function () {
                    var $input = $(this);
                    var name = $input.attr('name') || '';
                    var match = name.match(/return_qty\[(\d+)\]/);
                    if (!match) return;
                    var id = match[1];
                    var data = selected[id];
                    if (data && typeof data.qty !== 'undefined') {
                        var maxQty = parseFloat(data.qty);
                        if (!isFinite(maxQty) || maxQty < 0) {
                            maxQty = 0;
                        }
                        $input.val(maxQty > 0 ? maxQty : 0);
                    } else {
                        $input.val('0');
                    }
                });
            }
        });
    }

    function initOrderDiscountModal() {
        var $modal = $('[data-order-discount-modal]');
        if (!$modal.length) return;

        function setActiveType(type) {
            $modal.find('[data-order-discount-type-option]').each(function () {
                var $btn = $(this);
                var value = $btn.attr('data-order-discount-type-option') || 'none';
                var isActive = value === type;
                $btn.attr('data-active', isActive ? 'true' : 'false');
                if (isActive) {
                    $btn.addClass('bg-emerald-600 text-white');
                } else {
                    $btn.removeClass('bg-emerald-600 text-white');
                }
            });
            var showFixed = type === 'fixed';
            var showPercent = type === 'percent';
            $modal.find('[data-order-discount-fixed-modal-wrapper]').toggleClass('hidden', !showFixed);
            $modal.find('[data-order-discount-percent-modal-wrapper]').toggleClass('hidden', !showPercent);
        }

        $(document).on('click', '[data-order-discount-open]', function () {
            var $root = $('[data-pos-root]:visible').first();
            if (!$root.length) {
                $root = $('[data-order-edit-root]').first();
            }
            if (!$root.length) {
                $root = $('[data-transaction-form]').first();
            }
            if (!$root.length) return;

            var $typeHidden = $root.find('[data-order-discount-type]').first();
            var $valueHidden = $root.find('[data-order-discount-value]').first();

            var type = $typeHidden.length ? ($typeHidden.val() || 'none') : 'none';
            var valueNumber = $valueHidden.length ? parseFloat($valueHidden.val() || '0') : 0;
            if (!isFinite(valueNumber) || valueNumber < 0) {
                valueNumber = 0;
            }

            $modal.data('root', $root);

            setActiveType(type);

            var $fixedInput = $modal.find('[data-order-discount-fixed-modal]');
            var $percentInput = $modal.find('[data-order-discount-percent-modal]');

            if (type === 'fixed') {
                if ($fixedInput.length) {
                    var fixedText = valueNumber > 0 ? valueNumber.toString() : '0';
                    $fixedInput.val(fixedText);
                }
                if ($percentInput.length) {
                    $percentInput.val('0');
                }
            } else if (type === 'percent') {
                if ($percentInput.length) {
                    $percentInput.val(valueNumber.toString());
                }
                if ($fixedInput.length) {
                    $fixedInput.val('0');
                }
            } else {
                if ($fixedInput.length) {
                    $fixedInput.val('0');
                }
                if ($percentInput.length) {
                    $percentInput.val('0');
                }
            }

            $modal.removeClass('hidden');
        });

        $(document).on('click', '[data-order-discount-type-option]', function () {
            var $btn = $(this);
            var type = $btn.attr('data-order-discount-type-option') || 'none';
            setActiveType(type);
        });

        $(document).on('click', '[data-order-discount-cancel]', function () {
            $modal.addClass('hidden');
            $modal.removeData('root');
        });

        $(document).on('click', '[data-order-discount-save]', function () {
            var $root = $modal.data('root');
            if (!$root || !$root.length) {
                $modal.addClass('hidden');
                $modal.removeData('root');
                return;
            }

            var $typeHidden = $root.find('[data-order-discount-type]').first();
            var $valueHidden = $root.find('[data-order-discount-value]').first();

            var type = 'none';
            $modal.find('[data-order-discount-type-option]').each(function () {
                var $btn = $(this);
                if ($btn.attr('data-active') === 'true') {
                    type = $btn.attr('data-order-discount-type-option') || 'none';
                }
            });

            var valueNumber = 0;
            var $fixedInput = $modal.find('[data-order-discount-fixed-modal]');
            var $percentInput = $modal.find('[data-order-discount-percent-modal]');

            if (type === 'fixed') {
                var rawFixed = $fixedInput.length ? ($fixedInput.val() || '') : '';
                var numericFixed = rawFixed.replace(/[^\d]/g, '');
                valueNumber = parseFloat(numericFixed || '0');
            } else if (type === 'percent') {
                var rawPercent = $percentInput.length ? ($percentInput.val() || '0') : '0';
                valueNumber = parseFloat(rawPercent || '0');
            }

            if (!isFinite(valueNumber) || valueNumber < 0) {
                valueNumber = 0;
            }
            if (type === 'percent' && valueNumber > 100) {
                valueNumber = 100;
            }

            if ($typeHidden.length) {
                $typeHidden.val(type);
            }
            if ($valueHidden.length) {
                $valueHidden.val(valueNumber.toString());
            }

            if ($root.is('[data-pos-root]')) {
                var instanceForm = $('[data-pos-form]');
                if (instanceForm && instanceForm.length) {
                    instanceForm.trigger('discount:change');
                }
            } else if ($root.is('[data-order-edit-root]')) {
                $root.trigger('discount:change');
            } else if ($root.is('[data-transaction-form]')) {
                recalcTransactionTotals();
            }

            $modal.addClass('hidden');
            $modal.removeData('root');
        });
    }

    function initOrderSurchargeModal() {
        var $modal = $('[data-order-surcharge-modal]');
        if (!$modal.length) return;

        $(document).on('click', '[data-order-surcharge-open]', function () {
            var $root = $('[data-pos-root]:visible').first();
            if (!$root.length) {
                $root = $('[data-order-edit-root]').first();
            }
            if (!$root.length) {
                $root = $('[data-transaction-form]').first();
            }
            if (!$root.length) return;

            var $valueHidden;
            if ($root.is('[data-pos-root]')) {
                $valueHidden = $root.find('[data-pos-surcharge-hidden]').first();
            } else {
                $valueHidden = $root.find('[data-order-surcharge-value]').first();
            }

            var currentValue = $valueHidden.length ? parsePurchaseMoney($valueHidden.val() || '') : 0;
            if (!isFinite(currentValue) || currentValue < 0) {
                currentValue = 0;
            }

            $modal.data('root', $root);

            var $input = $modal.find('[data-order-surcharge-modal-input]');
            if ($input.length) {
                $input.val(currentValue > 0 ? currentValue.toString() : '0');
            }

            $modal.removeClass('hidden');
        });

        $(document).on('click', '[data-order-surcharge-cancel]', function () {
            $modal.addClass('hidden');
            $modal.removeData('root');
        });

        $(document).on('click', '[data-order-surcharge-save]', function () {
            var $root = $modal.data('root');
            if (!$root || !$root.length) {
                $modal.addClass('hidden');
                $modal.removeData('root');
                return;
            }

            var $input = $modal.find('[data-order-surcharge-modal-input]');
            var raw = $input.length ? ($input.val() || '') : '';
            var numeric = raw.toString().replace(/[^\d]/g, '');
            var valueNumber = parseFloat(numeric || '0');
            if (!isFinite(valueNumber) || valueNumber < 0) {
                valueNumber = 0;
            }

            var $valueHidden;
            if ($root.is('[data-pos-root]')) {
                $valueHidden = $root.find('[data-pos-surcharge-hidden]').first();
            } else {
                $valueHidden = $root.find('[data-order-surcharge-value]').first();
            }

            if ($valueHidden.length) {
                $valueHidden.val(valueNumber.toString());
            }

            if ($root.is('[data-pos-root]')) {
                var instanceForm = $('[data-pos-form]');
                if (instanceForm && instanceForm.length) {
                    instanceForm.trigger('discount:change');
                }
            } else if ($root.is('[data-order-edit-root]')) {
                $root.trigger('discount:change');
            } else if ($root.is('[data-transaction-form]')) {
                recalcTransactionTotals();
            }

            $modal.addClass('hidden');
            $modal.removeData('root');
        });
    }

    function initSelect2() {
        if (!$.fn || !$.fn.select2) return;

        function enhance($context) {
            $context.find('select').not('[data-no-select2]').each(function () {
                var $select = $(this);
                if ($select.data('select2')) return;

                if ($select.closest('.flatpickr-calendar').length) {
                    return;
                }

                $select.select2({
                    width: '100%'
                });
            });
        }

        enhance($(document));

        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                var $target = $(mutation.target);
                if ($target.is('select')) {
                    enhance($target.parent());
                } else {
                    enhance($target);
                }
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });
    }

    function initOrderEdit() {
        var $root = $('[data-order-edit-root]');
        if (!$root.length) return;

        var $customerModeButtons = $root.find('[data-order-customer-mode]');
        var $customerModeInput = $root.find('[data-order-customer-mode-input]');
        var $existingCustomerWrapper = $root.find('[data-order-existing-customer-wrapper]');
        var $existingCustomerPlaceholder = $root.find('[data-order-existing-customer-placeholder]');
        var $existingCustomerName = $root.find('[data-order-existing-customer-name]');
        var $existingCustomerMeta = $root.find('[data-order-existing-customer-meta]');
        var $newCustomerFields = $root.find('[data-order-new-customer]');
        var $customerIdInput = $root.find('input[name="customer_id"][data-order-customer-id]');
        var $customerModal = $('[data-pos-customer-modal]');
        var $customerSearchInput = $customerModal.find('[data-pos-customer-search]');
        var $customerList = $customerModal.find('[data-pos-customer-list]');
		var $paymentStatus = $root.find('input[name="payment_status"]');
		var $paymentMethodWrapper = $root.find('[data-order-payment-method-wrapper]');

        var units = window.ORDER_EDIT_PRODUCT_UNITS || [];
        var unitsById = {};
        var unitsByProduct = {};
        var $form = $root.find('form').first();
        var $itemsList = $root.find('[data-order-edit-items-list]');
        var $manualRoot = $root.find('[data-order-edit-manual-root]');
        var $manualRows = $manualRoot.length ? $manualRoot.find('[data-order-edit-manual-rows]') : $();
        var $subtotalEl = $root.find('[data-order-edit-subtotal]');
        var $totalEl = $root.find('[data-order-edit-total]');
        var $discountDisplay = $root.find('[data-order-edit-discount-amount]');
        var $discountValueHidden = $root.find('[data-order-discount-value]');
        var $discountAmountHidden = $root.find('[data-order-discount-amount-hidden]');
        var $manualEditModal = $('[data-pos-manual-edit-modal]');
        var currentManualRow = null;
        var $editName;
        var $editUnit;
        var $editQty;
        var $editPriceBuy;
        var $editAmountBuy;
        var $editPriceSell;
        var baseTotalRaw = $root.attr('data-order-base-total') || '0';
        var baseTotal = parseFloat(baseTotalRaw);
        if (!isFinite(baseTotal)) {
            baseTotal = 0;
        }
        var pendingItems = [];
        var removedExistingIds = [];
        var selectedCustomerId = null;

        $.each(units, function (index, row) {
            var id = String(row.id);
            unitsById[id] = row;
            var productId = row.product_id ? String(row.product_id) : '';
            if (productId) {
                if (!unitsByProduct[productId]) {
                    unitsByProduct[productId] = [];
                }
                unitsByProduct[productId].push(row);
            }
        });

        function setCustomerMode(mode) {
            if (!$customerModeInput.length) return;
            if (mode !== 'existing' && mode !== 'new' && mode !== 'guest') {
                mode = 'guest';
            }
            $customerModeInput.val(mode);
            if ($customerModeButtons.length) {
                $customerModeButtons.each(function () {
                    var $btn = $(this);
                    var btnMode = $btn.attr('data-order-customer-mode') || '';
                    var isActive = btnMode === mode;
                    $btn.toggleClass('bg-emerald-600 text-white shadow-sm', isActive);
                    $btn.toggleClass('bg-slate-100 text-slate-700', !isActive);
                });
            }
            var showExisting = mode === 'existing';
            var showNew = mode === 'new';
            if ($existingCustomerWrapper.length) {
                $existingCustomerWrapper.toggleClass('hidden', !showExisting);
            }
            if ($newCustomerFields.length) {
                $newCustomerFields.toggleClass('hidden', !showNew);
            }
            if (mode !== 'existing') {
                selectedCustomerId = null;
                if ($customerIdInput.length) {
                    $customerIdInput.val('');
                }
                if ($existingCustomerName.length) {
                    $existingCustomerName.text('Chưa chọn khách');
                }
                if ($existingCustomerMeta.length) {
                    $existingCustomerMeta.text('Nhấn để chọn khách từ danh sách');
                }
            }
        }

        function highlightSelectedOrderCustomer() {
            if (!$customerList.length) return;
            $customerList.find('[data-pos-customer-item]').each(function () {
                var $item = $(this);
                var id = String($item.attr('data-customer-id') || '');
                var active = selectedCustomerId != null && String(selectedCustomerId) === id;
                $item.toggleClass('bg-emerald-50', active);
                var $indicator = $item.find('[data-pos-customer-selected-indicator]');
                if ($indicator.length) {
                    $indicator.toggleClass('hidden', !active);
                }
            });
        }

        function filterOrderCustomerList(keyword) {
            if (!$customerList.length) return;
            var q = (keyword || '').toString().toLowerCase().trim();
            $customerList.find('[data-pos-customer-item]').each(function () {
                var $item = $(this);
                var text = ($item.attr('data-search-text') || $item.text() || '').toLowerCase();
                var match = !q || text.indexOf(q) !== -1;
                $item.toggleClass('hidden', !match);
            });
        }

        function openOrderCustomerModal() {
            if (!$customerModal.length) return;
            $customerModal.removeClass('hidden').addClass('flex');
            if ($customerSearchInput.length) {
                $customerSearchInput.val('');
            }
            filterOrderCustomerList('');
            selectedCustomerId = null;
            var currentMode = $customerModeInput.length ? ($customerModeInput.val() || 'guest') : 'guest';
            if (currentMode === 'existing' && $customerIdInput.length) {
                var rawId = $.trim($customerIdInput.val() || '');
                var parsedId = parseInt(rawId, 10);
                if (parsedId && parsedId > 0) {
                    selectedCustomerId = String(parsedId);
                }
            }
            highlightSelectedOrderCustomer();
        }

        function closeOrderCustomerModal() {
            if (!$customerModal.length) return;
            $customerModal.addClass('hidden').removeClass('flex');
        }

        function applySelectedOrderCustomer() {
            if (!selectedCustomerId || !$customerIdInput.length) {
                closeOrderCustomerModal();
                return;
            }
            $customerIdInput.val(String(selectedCustomerId));
            if ($existingCustomerName.length && $existingCustomerMeta.length) {
                var selector = '[data-pos-customer-item][data-customer-id="' + String(selectedCustomerId) + '"]';
                var $item = $customerList.find(selector).first();
                if ($item.length) {
                    var name = $item.attr('data-customer-name') || '';
                    var phone = $item.attr('data-customer-phone') || '';
                    var address = $item.attr('data-customer-address') || '';
                    var line1 = name;
                    if (phone) {
                        line1 += ' - ' + phone;
                    }
                    $existingCustomerName.text(line1 || 'Chưa chọn khách');
                    $existingCustomerMeta.text(address || '');
                }
            }
            closeOrderCustomerModal();
        }

        function togglePaymentMethod() {
            if (!$paymentStatus.length || !$paymentMethodWrapper.length) return;
            var current = $paymentStatus.filter(':checked').val();
            $paymentMethodWrapper.toggleClass('hidden', current !== 'pay');
        }

        function formatOrderCurrency(value) {
            var number = typeof value === 'number' ? value : parseFloat(value || 0);
            if (!isFinite(number)) number = 0;
            return number.toLocaleString('vi-VN');
        }

        function openOrderEditManualModal($row) {
            if (!$manualEditModal.length || !$row || !$row.length) return;
            if (!$editName || !$editName.length) return;
            currentManualRow = $row;

            var nameVal = $row.find('input[name="manual_item_name[]"]').val() || '';
            var unitVal = $row.find('input[name="manual_unit_name[]"]').val() || '';
            var qtyVal = $row.find('input[name="manual_qty[]"]').val() || '';
            var priceBuyVal = $row.find('input[name="manual_price_buy[]"]').val() || '';
            var priceSellVal = $row.find('input[name="manual_price_sell[]"]').val() || '';

            $editName.val(nameVal);
            $editUnit.val(unitVal);
            $editQty.val(qtyVal);
            $editPriceBuy.val(priceBuyVal);
            $editPriceSell.val(priceSellVal);

            var qtyNum = parseFloat(qtyVal.toString().replace(',', '.'));
            if (!isFinite(qtyNum) || qtyNum <= 0) {
                qtyNum = 0;
            }
            var priceBuyNum = parsePurchaseMoney(priceBuyVal);
            var amountBuy = 0;
            if (qtyNum > 0 && priceBuyNum > 0) {
                amountBuy = qtyNum * priceBuyNum;
            }
            if ($editAmountBuy && $editAmountBuy.length) {
                if (amountBuy > 0) {
                    $editAmountBuy.val(formatOrderCurrency(amountBuy));
                } else {
                    $editAmountBuy.val('');
                }
            }

            $manualEditModal.removeClass('hidden').addClass('flex');
        }

        function closeOrderEditManualModal() {
            if (!$manualEditModal.length) return;
            $manualEditModal.addClass('hidden').removeClass('flex');
            currentManualRow = null;
        }

        if ($manualEditModal.length) {
            $editName = $manualEditModal.find('[data-pos-manual-edit-name]');
            $editUnit = $manualEditModal.find('[data-pos-manual-edit-unit]');
            $editQty = $manualEditModal.find('[data-pos-manual-edit-qty]');
            $editPriceBuy = $manualEditModal.find('[data-pos-manual-edit-price-buy]');
            $editAmountBuy = $manualEditModal.find('[data-pos-manual-edit-amount-buy]');
            $editPriceSell = $manualEditModal.find('[data-pos-manual-edit-price-sell]');

            var updateManualAmountFromPrice = function () {
                if (!$editQty || !$editPriceBuy || !$editAmountBuy) return;
                var qtyVal = $editQty.val() || '';
                var priceVal = $editPriceBuy.val() || '';
                var qtyNum = parseFloat(qtyVal.toString().replace(',', '.'));
                if (!isFinite(qtyNum) || qtyNum <= 0) {
                    qtyNum = 0;
                }
                var priceNum = parsePurchaseMoney(priceVal);
                var amount = 0;
                if (qtyNum > 0 && priceNum > 0) {
                    amount = qtyNum * priceNum;
                }
                if ($editAmountBuy.length) {
                    if (amount > 0) {
                        $editAmountBuy.val(formatOrderCurrency(amount));
                    } else {
                        $editAmountBuy.val('');
                    }
                }
            };

            var updateManualPriceFromAmount = function () {
                if (!$editAmountBuy || !$editAmountBuy.length) return;
                var qtyVal = $editQty.val() || '';
                var amountVal = $editAmountBuy.val() || '';
                var qtyNum = parseFloat(qtyVal.toString().replace(',', '.'));
                if (!isFinite(qtyNum) || qtyNum <= 0) {
                    return;
                }
                var amountNum = parsePurchaseMoney(amountVal);
                if (!isFinite(amountNum) || amountNum <= 0) {
                    return;
                }
                var newPrice = Math.round(amountNum / qtyNum);
                if (isFinite(newPrice) && newPrice > 0) {
                    $editPriceBuy.val(formatOrderCurrency(newPrice));
                }
            };

            $editQty.on('input', updateManualAmountFromPrice);
            $editPriceBuy.on('input', updateManualAmountFromPrice);
            if ($editAmountBuy.length) {
                $editAmountBuy.on('input', updateManualPriceFromAmount);
            }

            $(document).on('click', '[data-pos-manual-edit-cancel]', function (e) {
                e.preventDefault();
                closeOrderEditManualModal();
            });

            $(document).on('click', '[data-pos-manual-edit-save]', function (e) {
                e.preventDefault();
                if (!currentManualRow || !currentManualRow.length) {
                    closeOrderEditManualModal();
                    return;
                }

                var nameVal = $editName.val() || '';
                var unitVal = $editUnit.val() || '';
                var qtyVal = $editQty.val() || '';
                var priceBuyVal = $editPriceBuy.val() || '';
                var amountBuyVal = $editAmountBuy && $editAmountBuy.length ? $editAmountBuy.val() || '' : '';
                var priceSellVal = $editPriceSell.val() || '';

                var qtyNum = parseFloat(qtyVal.toString().replace(',', '.'));
                if (!isFinite(qtyNum) || qtyNum <= 0) {
                    qtyNum = 0;
                }
                var amountBuyNum = parsePurchaseMoney(amountBuyVal);
                var priceBuyNum = parsePurchaseMoney(priceBuyVal);

                if (qtyNum > 0 && amountBuyNum > 0) {
                    var newPriceBuy = Math.round(amountBuyNum / qtyNum);
                    if (isFinite(newPriceBuy) && newPriceBuy > 0) {
                        priceBuyNum = newPriceBuy;
                        priceBuyVal = formatOrderCurrency(priceBuyNum);
                    }
                } else if (priceBuyNum > 0 && qtyNum > 0 && $editAmountBuy && $editAmountBuy.length) {
                    var recalculatedAmount = priceBuyNum * qtyNum;
                    $editAmountBuy.val(formatOrderCurrency(recalculatedAmount));
                }

                currentManualRow.find('input[name="manual_item_name[]"]').val(nameVal);
                currentManualRow.find('input[name="manual_unit_name[]"]').val(unitVal);
                currentManualRow.find('input[name="manual_qty[]"]').val(qtyVal);
                currentManualRow.find('input[name="manual_price_buy[]"]').val(priceBuyVal);
                currentManualRow.find('input[name="manual_price_sell[]"]').val(priceSellVal);

                var displayQty = qtyVal || '0';
                var priceSellNum = parsePurchaseMoney(priceSellVal);
                var displaySell = priceSellNum > 0 ? formatOrderCurrency(priceSellNum) + ' đ' : '0 đ';
                var lineSellTotal = 0;
                if (qtyNum > 0 && priceSellNum > 0) {
                    lineSellTotal = qtyNum * priceSellNum;
                }

                currentManualRow.find('[data-order-edit-manual-display-name]').text(nameVal || 'Chưa nhập tên hàng');
                currentManualRow.find('[data-order-edit-manual-display-unit]').text(unitVal ? ' - ' + unitVal : '');
                currentManualRow.find('[data-order-edit-manual-display-qty]').text(displayQty);
                currentManualRow.find('[data-order-edit-manual-display-sell]').text(displaySell);
                currentManualRow.find('[data-order-edit-manual-amount]').text(lineSellTotal > 0 ? formatOrderCurrency(lineSellTotal) + ' đ' : '0 đ');

                closeOrderEditManualModal();
                recalcOrderTotals();
            });

            $manualEditModal.on('click', function (e) {
                if (e.target !== this) return;
                closeOrderEditManualModal();
            });

            $(document).on('keydown', function (e) {
                if ($manualEditModal.hasClass('hidden')) return;
                if (e.key === 'Escape') {
                    e.preventDefault();
                    closeOrderEditManualModal();
                }
            });
        }

        function recalcOrderTotals() {
            var base = 0;
            var hasAnyItems = false;
            if ($itemsList.length) {
                $itemsList.find('[data-order-existing-item]').each(function () {
                    var $row = $(this);
                    var $amountEl = $row.find('[data-order-existing-amount]');
                    if (!$amountEl.length) return;
                    var raw = $amountEl.text() || '0';
                    var numeric = raw.replace(/[^\d]/g, '');
                    var amount = parseFloat(numeric || '0');
                    if (!isFinite(amount) || amount < 0) {
                        amount = 0;
                    }
                    base += amount;
                    hasAnyItems = true;
                });
                if (!hasAnyItems) {
                    hasAnyItems = $itemsList.find('[data-order-edit-new-item]').length > 0;
                }
                var $emptyBox = $itemsList.find('[data-order-edit-empty]').first();
                if ($emptyBox.length) {
                    $emptyBox.toggleClass('hidden', hasAnyItems);
                }
            }
            baseTotal = base;

            var addTotal = 0;
            $.each(pendingItems, function (index, item) {
                var unit = unitsById[String(item.product_unit_id)];
                if (!unit) return;
                var qty = parseFloat(item.qty || 0);
                if (!isFinite(qty) || qty <= 0) return;
                var price = 0;
                if ($itemsList.length) {
                    var $row = $itemsList.find('[data-order-edit-new-item][data-product-unit-id="' + String(unit.id) + '"]').first();
                    if ($row.length) {
                        var priceRaw = $row.attr('data-price') || '0';
                        price = parseFloat(priceRaw);
                    }
                }
                if (!isFinite(price) || price <= 0) {
                    price = parseFloat(unit.price || unit.price_sell || 0);
                }
                if (!isFinite(price) || price <= 0) return;
                addTotal += price * qty;
            });

            var manualTotal = 0;
            var manualCount = 0;
            if ($manualRows.length) {
                $manualRows.find('.order-edit-manual-item-row').each(function () {
                    var $row = $(this);
                    var qtyRaw = $row.find('input[name="manual_qty[]"]').val() || '';
                    var qty = parseFloat(qtyRaw.toString().replace(',', '.'));
                    if (!isFinite(qty) || qty <= 0) {
                        qty = 0;
                    }
                    var priceSellRaw = $row.find('input[name="manual_price_sell[]"]').val() || '';
                    var priceSell = parsePurchaseMoney(priceSellRaw);
                    if (!isFinite(priceSell) || priceSell < 0) {
                        priceSell = 0;
                    }
                    var amountSell = 0;
                    if (qty > 0 && priceSell > 0) {
                        amountSell = qty * priceSell;
                    }
                    if (amountSell > 0) {
                        manualTotal += amountSell;
                    }
                    if (qty > 0 || priceSell > 0) {
                        manualCount++;
                    }
                    var $amountDisplay = $row.find('[data-order-edit-manual-amount]');
                    if ($amountDisplay.length) {
                        if (amountSell > 0) {
                            $amountDisplay.text(formatOrderCurrency(amountSell) + ' đ');
                        } else {
                            $amountDisplay.text('0 đ');
                        }
                    }
                });
            }
            if ($manualRoot.length) {
                var $manualEmpty = $manualRoot.find('[data-order-edit-manual-empty]').first();
                if ($manualEmpty.length) {
                    $manualEmpty.toggleClass('hidden', manualCount > 0);
                }
            }

            var subtotal = baseTotal + addTotal + manualTotal;
            if (!isFinite(subtotal) || subtotal < 0) {
                subtotal = 0;
            }

            var discountType = $root.find('[data-order-discount-type]').val() || 'none';
            var discountValue = 0;
            var discountAmount = 0;
            var surchargeAmount = 0;

            if ($discountValueHidden.length) {
                discountValue = parseFloat($discountValueHidden.val() || '0');
                if (!isFinite(discountValue) || discountValue < 0) {
                    discountValue = 0;
                }
            }

            if (discountType === 'fixed') {
                discountAmount = discountValue;
            } else if (discountType === 'percent') {
                if (discountValue > 100) {
                    discountValue = 100;
                }
                discountAmount = Math.round(subtotal * discountValue / 100);
            }

            if (discountAmount < 0) {
                discountAmount = 0;
            }
            if (discountAmount > subtotal) {
                discountAmount = subtotal;
            }

            var $surchargeHidden = $root.find('[data-order-surcharge-value]').first();
            if ($surchargeHidden.length) {
                var surchargeRaw = $surchargeHidden.val() || '0';
                surchargeAmount = parsePurchaseMoney(surchargeRaw);
                if (!isFinite(surchargeAmount) || surchargeAmount < 0) {
                    surchargeAmount = 0;
                }
            }

            var rawTotal = subtotal - discountAmount + surchargeAmount;
            if (!isFinite(rawTotal) || rawTotal < 0) {
                rawTotal = 0;
            }

            var total = roundDownThousand(rawTotal);

            if ($subtotalEl.length) {
                $subtotalEl.text(formatOrderCurrency(subtotal) + ' đ');
            }
            if ($discountDisplay.length) {
                $discountDisplay.text('-' + formatOrderCurrency(discountAmount) + ' đ');
            }
            var $surchargeDisplay = $root.find('[data-order-edit-surcharge-amount]');
            if ($surchargeDisplay.length) {
                $surchargeDisplay.text('+' + formatOrderCurrency(surchargeAmount) + ' đ');
            }
            if ($totalEl.length) {
                $totalEl.text(formatOrderCurrency(total) + ' đ');
            }
            if ($discountValueHidden.length) {
                $discountValueHidden.val(discountValue);
            }
            if ($discountAmountHidden.length) {
                $discountAmountHidden.val(discountAmount);
            }
        }

        function syncOrderHiddenInputs() {
            if (!$form.length) return;
            $form.find('input[name="product_unit_id[]"], input[name="qty[]"], input[name="mode[]"], input[name="remove_existing[]"], input[name^="existing_price["], input[name="price[]"]').remove();

            $.each(removedExistingIds, function (index, id) {
                if (!id) return;
                var $removeInput = $('<input>', {
                    type: 'hidden',
                    name: 'remove_existing[]',
                    value: id
                });
                $form.append($removeInput);
            });
            $.each(pendingItems, function (index, item) {
                var unitId = item.product_unit_id;
                var qty = item.qty;
                if (!unitId) return;
                var qtyNumber = parseFloat(qty || 0);
                if (!isFinite(qtyNumber) || qtyNumber <= 0) return;
                var mode = item.mode || 'delta';
                var $unitInput = $('<input>', {
                    type: 'hidden',
                    name: 'product_unit_id[]',
                    value: unitId
                });
                var $qtyInput = $('<input>', {
                    type: 'hidden',
                    name: 'qty[]',
                    value: qtyNumber
                });
                var $modeInput = $('<input>', {
                    type: 'hidden',
                    name: 'mode[]',
                    value: mode
                });
                var priceNumber = 0;
                if ($itemsList.length) {
                    var $rowNew = $itemsList.find('[data-order-edit-new-item][data-product-unit-id="' + String(unitId) + '"]').first();
                    if ($rowNew.length) {
                        var priceRaw = $rowNew.attr('data-price') || '0';
                        priceNumber = parseFloat(priceRaw || '0');
                    }
                }
                if (!isFinite(priceNumber) || priceNumber < 0) {
                    priceNumber = 0;
                }
                var $priceInput = $('<input>', {
                    type: 'hidden',
                    name: 'price[]',
                    value: priceNumber
                });
                $form.append($unitInput).append($qtyInput).append($modeInput).append($priceInput);
            });

            if ($itemsList.length) {
                $itemsList.find('[data-order-existing-item]').each(function () {
                    var $row = $(this);
                    var idRaw = $row.attr('data-order-item-id') || '';
                    var id = parseInt(idRaw, 10);
                    if (!id) return;
                    var basePriceRaw = $row.attr('data-base-price') || '0';
                    var basePrice = parseFloat(basePriceRaw || '0');
                    if (!isFinite(basePrice) || basePrice < 0) {
                        basePrice = 0;
                    }
                    var priceRaw = $row.attr('data-price') || basePriceRaw;
                    var price = parseFloat(priceRaw || '0');
                    if (!isFinite(price) || price < 0) {
                        price = basePrice;
                    }
                    if (!isFinite(price) || price < 0) {
                        price = 0;
                    }
                    if (Math.abs(price - basePrice) < 0.0001) {
                        return;
                    }
                    var $priceInputExisting = $('<input>', {
                        type: 'hidden',
                        name: 'existing_price[' + id + ']',
                        value: price
                    });
                    $form.append($priceInputExisting);
                });
            }
        }

        function renderNewItems() {
            if (!$itemsList.length) {
                recalcOrderTotals();
                syncOrderHiddenInputs();
                return;
            }
            $itemsList.find('[data-order-edit-new-item]').remove();
            $.each(pendingItems, function (index, item) {
                var unit = unitsById[String(item.product_unit_id)];
                if (!unit) return;
                var qty = parseFloat(item.qty || 0);
                if (!isFinite(qty) || qty <= 0) return;

                var mode = item.mode || 'delta';
                var hasExisting = $itemsList.find('[data-order-existing-item][data-product-unit-id="' + String(unit.id) + '"]').length > 0;
                if (hasExisting && mode !== 'new') return;

                var price = parseFloat(unit.price || unit.price_sell || 0);
                if (!isFinite(price) || price < 0) {
                    price = 0;
                }
                var amount = qty * price;

                var $row = $('<div>', {
                    'data-order-edit-new-item': '1',
                    'data-product-unit-id': unit.id,
                    'data-price': price,
                    'data-base-price': price,
                    "class": 'flex items-center justify-between gap-3 py-2 border-b border-slate-200 last:border-b-0'
                });

                var $left = $('<div>', { "class": 'flex items-center gap-3' });
				var $thumb = $('<div>', { "class": 'flex h-12 w-12 items-center justify-center overflow-hidden rounded-xl bg-slate-100 text-sm font-medium text-slate-400' });
				if (unit.image_url) {
					var $img = $('<img>', {
						src: unit.image_url,
						alt: unit.product_name || '',
						"class": 'h-full w-full object-cover'
					});
					$thumb.append($img);
				} else {
					$thumb.html('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">\n  <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />\n</svg>');
				}

                var $info = $('<div>');
                var $productText = $('<div>', { "class": 'text-sm font-medium text-slate-900' }).text(unit.product_name || '');
                $info.append($productText);

                var priceText = unit.price_text || formatOrderCurrency(price);
                var unitNameText = unit.unit_name ? ('/ ' + unit.unit_name) : '';
                var $unitInfo = $('<div>', { "class": 'mt-0.5 text-sm text-slate-500' });
                var $priceBtn = $('<button>', {
                    type: 'button',
                    'data-order-price-edit': '1',
                    "class": 'inline-flex items-center gap-1 rounded-full px-2 py-0.5 hover:bg-emerald-50'
                });
                var $priceSpan = $('<span>', {
                    'data-order-price-display': '1',
                    "class": 'font-medium text-slate-900'
                }).text(priceText);
                var $unitSpan = $('<span>').text(unitNameText);
                var $editIcon = $('<span>', {
                    "class": 'inline-flex h-4 w-4 items-center justify-center text-slate-400 group-hover:text-emerald-600'
                }).html('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3 w-3"><path d="M13.586 3.586a2 2 0 0 1 2.828 2.828l-.793.793-2.828-2.828.793-.793ZM11.379 5.793 4 13.172V16h2.828l7.38-7.379-2.83-2.828Z" /></svg>');
                $priceBtn.append($priceSpan).append($unitSpan).append($editIcon);
                $unitInfo.append($priceBtn);
                $info.append($unitInfo);

                var $qtyGroup = $('<div>', { "class": 'inline-flex items-stretch overflow-hidden rounded-full border border-slate-300 bg-slate-50' });
                var $decreaseBtn = $('<button>', {
                    type: 'button',
                    'data-order-edit-decrease': '1',
                    "class": 'inline-flex h-6 w-6 items-center justify-center bg-slate-50 text-sm text-slate-700 hover:bg-slate-100'
                }).html('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3">\n  <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />\n</svg>');
                var min = 1;
                var step = 1;
                if (unit.allow_fraction) {
                    step = unit.min_step && unit.min_step > 0 ? unit.min_step : 0.1;
                    min = step;
                }
                var $qtyInput = $('<input>', {
                    type: 'number',
                    min: String(min),
                    step: String(step),
                    value: String(qty),
                    'data-order-edit-qty': '1',
                    "class": 'h-6 w-10 border-0 bg-slate-50 px-1 text-sm font-medium text-center outline-none'
                });
                var $increaseBtn = $('<button>', {
                    type: 'button',
                    'data-order-edit-increase': '1',
                    "class": 'inline-flex h-6 w-6 items-center justify-center bg-slate-50 text-sm text-slate-700 hover:bg-slate-100'
                }).html('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3">\n  <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />\n</svg>');

                $qtyGroup.append($decreaseBtn).append($qtyInput).append($increaseBtn);
				var $qtyWrapper = $('<div>', { "class": 'mt-1' }).append($qtyGroup);
                $info.append($qtyWrapper);

                $left.append($thumb).append($info);

                var $right = $('<div>', { "class": 'flex flex-col items-end justify-between gap-2 flex-1' });
                var $topRow = $('<div>', { "class": 'flex w-full justify-end' });

                var $line = $('<div>', { "class": 'text-sm font-medium text-emerald-600' });
                var $lineTotal = $('<span>', {
                    'data-order-new-amount': '1'
                }).text(formatOrderCurrency(amount));
                $line.append($lineTotal).append(' đ');

                var $removeBtn = $('<button>', {
                    type: 'button',
                    'data-order-edit-remove': '1',
                    "class": 'inline-flex h-5 w-5 items-center justify-center text-rose-500 hover:text-rose-600'
                }).html('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">\n  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />\n</svg>');

                $topRow.append($removeBtn);
                $right.append($topRow).append($line);

                $row.append($left).append($right);
                $itemsList.append($row);
            });

            recalcOrderTotals();
            syncOrderHiddenInputs();
        }

        function setPendingQty(unitId, qty) {
            var targetId = String(unitId);
            var qtyNumber = parseFloat(qty || 0);
            if (!isFinite(qtyNumber) || qtyNumber < 0) {
                qtyNumber = 0;
            }
            if (qtyNumber <= 0) {
                pendingItems = $.grep(pendingItems, function (item) {
                    return String(item.product_unit_id) !== targetId;
                });
                recalcOrderTotals();
                syncOrderHiddenInputs();
                return;
            }
            var found = false;
            $.each(pendingItems, function (index, item) {
                if (String(item.product_unit_id) === targetId) {
                    item.qty = qtyNumber;
                    found = true;
                    return false;
                }
            });
            if (!found) {
                pendingItems.push({
                    product_unit_id: unitId,
                    qty: qtyNumber,
                    mode: 'delta'
                });
            }
        }

        $root.on('click', '[data-order-edit-manual-add-row]', function (e) {
            e.preventDefault();
            if (!$manualRoot.length) return;
            var $template = $manualRoot.find('[data-order-edit-manual-row-template] .order-edit-manual-item-row').first();
            if (!$template.length) return;
            var $row = $template.clone(true);
            $row.find('input[name="manual_item_name[]"]').val('');
            $row.find('input[name="manual_unit_name[]"]').val('');
            $row.find('input[name="manual_qty[]"]').val('');
            $row.find('input[name="manual_price_buy[]"]').val('');
            $row.find('input[name="manual_price_sell[]"]').val('');
            $row.find('[data-order-edit-manual-display-name]').text('Chưa nhập tên hàng');
            $row.find('[data-order-edit-manual-display-unit]').text('');
            $row.find('[data-order-edit-manual-display-qty]').text('0');
            $row.find('[data-order-edit-manual-display-sell]').text('0 đ');
            $row.find('[data-order-edit-manual-amount]').text('0 đ');
            if ($manualRows.length) {
                $manualRows.append($row);
            } else {
                $manualRoot.append($row);
            }
            openOrderEditManualModal($row);
        });

        $root.on('click', '.order-edit-manual-item-row', function (e) {
            if ($(e.target).closest('[data-order-edit-manual-remove-row]').length) {
                return;
            }
            e.preventDefault();
            openOrderEditManualModal($(this));
        });

        $root.on('click', '[data-order-edit-manual-remove-row]', function (e) {
            e.preventDefault();
            var $row = $(this).closest('.order-edit-manual-item-row');
            if ($row.length) {
                $row.remove();
                recalcOrderTotals();
            }
        });

        $(document).on('click', '[data-order-price-edit]', function () {
            var $btn = $(this);
            var $row = $btn.closest('[data-order-existing-item], [data-order-edit-new-item]');
            if (!$row.length) return;
            var $modal = $('[data-pos-price-modal]');
            if (!$modal.length) return;
            var basePriceRaw = $row.attr('data-base-price') || $row.attr('data-price') || '0';
            var basePrice = parseFloat(basePriceRaw);
            if (!isFinite(basePrice) || basePrice < 0) {
                basePrice = 0;
            }
            var currentPriceRaw = $row.attr('data-price') || basePriceRaw;
            var currentPrice = parseFloat(currentPriceRaw);
            if (!isFinite(currentPrice) || currentPrice < 0) {
                currentPrice = basePrice;
            }
            var productName = $row.find('.text-sm.font-medium.text-slate-900').first().text() || '';
            $modal.data('row', $row);
            $modal.data('basePrice', basePrice);
            $modal.data('currentPrice', currentPrice);
            $modal.find('[data-pos-price-modal-product]').text(productName);
            $modal.find('[data-pos-price-modal-base]').text(formatCurrency(basePrice) + ' đ');
            $modal.find('[data-pos-price-modal-current]').text(formatCurrency(currentPrice) + ' đ');
            var $input = $modal.find('[data-pos-price-modal-input]');
            if ($input.length) {
                $input.val(currentPrice > 0 ? formatCurrency(currentPrice) : '0');
            }
            $modal.removeClass('hidden');
        });

        $(document).on('click', '[data-pos-price-cancel]', function () {
            var $modal = $('[data-pos-price-modal]');
            if (!$modal.length) return;
            $modal.addClass('hidden');
            $modal.removeData('row').removeData('basePrice').removeData('currentPrice');
        });

        $(document).on('click', '[data-pos-price-save]', function () {
            var $modal = $('[data-pos-price-modal]');
            if (!$modal.length) return;
            var $row = $modal.data('row');
            if (!$row || !$row.length) {
                $modal.addClass('hidden');
                return;
            }
            var basePrice = parseFloat($modal.data('basePrice') || '0');
            if (!isFinite(basePrice) || basePrice < 0) {
                basePrice = 0;
            }
            var $input = $modal.find('[data-pos-price-modal-input]');
            if (!$input.length) return;
            var raw = $input.val() != null ? $input.val().toString() : '';
            var numeric = raw.replace(/[^\d]/g, '');
            var newPrice = parseFloat(numeric || '0');
            if (!isFinite(newPrice) || newPrice <= 0) {
                newPrice = basePrice;
            }
            if (!isFinite(newPrice) || newPrice < 0) {
                newPrice = 0;
            }
            var price = newPrice;

            var $displayOrder = $row.find('[data-order-price-display]');
            $row.attr('data-price', price);
            var displayTextOrder = price > 0 ? (formatCurrency(price) + ' đ') : '0 đ';
            $displayOrder.text(displayTextOrder);
            $displayOrder.removeClass('text-amber-700');
            $displayOrder.removeClass('text-slate-900');
            if (basePrice > 0 && price < basePrice - 0.0001) {
                $displayOrder.addClass('text-amber-700');
            } else {
                $displayOrder.addClass('text-slate-900');
            }

            if ($row.is('[data-order-existing-item]')) {
                var $qtyInputExisting = $row.find('input[data-order-existing-qty-input]');
                var qtyRaw = $qtyInputExisting.length ? $qtyInputExisting.val() : '';
                var qty = parseFloat(qtyRaw || '0');
                if (!isFinite(qty) || qty < 0) {
                    qty = 0;
                }
                var newAmount = qty * price;
                $row.find('[data-order-existing-amount]').text(formatOrderCurrency(newAmount));
            } else if ($row.is('[data-order-edit-new-item]')) {
                var $qtyInputNew = $row.find('input[data-order-edit-qty]');
                var qtyRawNew = $qtyInputNew.length ? $qtyInputNew.val() : '';
                var qtyNew = parseFloat(qtyRawNew || '0');
                if (!isFinite(qtyNew) || qtyNew < 0) {
                    qtyNew = 0;
                }
                var newAmountNew = qtyNew * price;
                $row.find('[data-order-new-amount]').text(formatOrderCurrency(newAmountNew));
            }

            recalcOrderTotals();
            syncOrderHiddenInputs();

            $modal.addClass('hidden');
            $modal.removeData('row').removeData('basePrice').removeData('currentPrice');
        });

        function changePendingQty(unitId, delta) {
            var targetId = String(unitId);
            var updated = false;
            $.each(pendingItems, function (index, item) {
                if (String(item.product_unit_id) === targetId) {
                    var qtyNumber = parseFloat(item.qty || 0);
                    if (!isFinite(qtyNumber) || qtyNumber < 0) {
                        qtyNumber = 0;
                    }
                    qtyNumber = qtyNumber + delta;
                    if (qtyNumber <= 0) {
                        qtyNumber = 1;
                    }
                    item.qty = qtyNumber;
                    updated = true;
                    return false;
                }
            });
            if (!updated) {
                if (delta <= 0) {
                    setPendingQty(unitId, 1);
                } else {
                    setPendingQty(unitId, delta);
                }
            }
        }

        if ($customerModeButtons.length && $customerModeInput.length) {
            $customerModeButtons.on('click', function () {
                var mode = $(this).attr('data-order-customer-mode') || 'guest';
                setCustomerMode(mode);
            });
            var initialMode = $customerModeInput.val() || 'guest';
            setCustomerMode(initialMode);
        }

        if ($existingCustomerPlaceholder.length && $customerModal.length) {
            $existingCustomerPlaceholder.on('click', function () {
                openOrderCustomerModal();
            });
        }

        if ($customerList.length) {
            $customerList.on('click', '[data-pos-customer-item]', function () {
                var $item = $(this);
                selectedCustomerId = $item.attr('data-customer-id') || null;
                highlightSelectedOrderCustomer();
            });
        }

        if ($customerSearchInput.length) {
            $customerSearchInput.on('input', function () {
                filterOrderCustomerList($(this).val() || '');
            });
        }

        if ($customerModal.length) {
            $customerModal.on('click', function (e) {
                if (e.target === this) {
                    closeOrderCustomerModal();
                }
            });
            $customerModal.find('[data-pos-customer-cancel]').on('click', function () {
                closeOrderCustomerModal();
            });
            $customerModal.find('[data-pos-customer-confirm]').on('click', function () {
                applySelectedOrderCustomer();
            });
        }

        if ($paymentStatus.length && $paymentMethodWrapper.length) {
            $paymentStatus.on('change', function () {
                togglePaymentMethod();
            });
            togglePaymentMethod();
        }

        $root.on('discount:change', function () {
            recalcOrderTotals();
            syncOrderHiddenInputs();
        });

        recalcOrderTotals();

        $root.on('click', '[data-order-total-round]', function () {
            recalcOrderTotals();
            syncOrderHiddenInputs();
        });

        $(document).on('click', '[data-order-existing-increase]', function () {
            var $row = $(this).closest('[data-order-existing-item]');
            if (!$row.length) return;
            var unitId = String($row.attr('data-product-unit-id') || '');
            if (!unitId) return;
            var baseQtyRaw = $row.attr('data-base-qty') || '0';
            var baseQty = parseFloat(baseQtyRaw);
            if (!isFinite(baseQty) || baseQty < 0) baseQty = 0;

            var delta = 0;
            $.each(pendingItems, function (index, item) {
                if (String(item.product_unit_id) === unitId) {
                    var current = parseFloat(item.qty || 0);
                    if (!isFinite(current) || current < 0) current = 0;
                    delta = current;
                    return false;
                }
            });

            var step = 1;
            var $qtyInputStep = $row.find('input[data-order-existing-qty-input]');
            if ($qtyInputStep.length) {
                var s = parseFloat($qtyInputStep.attr('step') || '1');
                if (isFinite(s) && s > 0) {
                    step = s;
                }
            }

            delta = delta + step;
            setPendingQty(unitId, delta);

            var newQty = baseQty + delta;
            $row.find('[data-order-existing-qty]').text(formatOrderCurrency(newQty));
            var $qtyInput = $row.find('input[data-order-existing-qty-input]');
            if ($qtyInput.length) {
                $qtyInput.val(newQty);
            }

            var priceRaw = $row.attr('data-price') || '0';
            var price = parseFloat(priceRaw);
            if (!isFinite(price) || price < 0) price = 0;
            var newAmount = newQty * price;
            $row.find('[data-order-existing-amount]').text(formatOrderCurrency(newAmount));

            recalcOrderTotals();
            syncOrderHiddenInputs();
        });

        $(document).on('click', '[data-order-existing-decrease]', function () {
            var $row = $(this).closest('[data-order-existing-item]');
            if (!$row.length) return;
            var unitId = String($row.attr('data-product-unit-id') || '');
            if (!unitId) return;
            var baseQtyRaw = $row.attr('data-base-qty') || '0';
            var baseQty = parseFloat(baseQtyRaw);
            if (!isFinite(baseQty) || baseQty < 0) baseQty = 0;

            var delta = 0;
            $.each(pendingItems, function (index, item) {
                if (String(item.product_unit_id) === unitId) {
                    var current = parseFloat(item.qty || 0);
                    if (!isFinite(current) || current < 0) current = 0;
                    delta = current;
                    return false;
                }
            });

            var step = 1;
            var min = 0;
            var $qtyInputStep = $row.find('input[data-order-existing-qty-input]');
            if ($qtyInputStep.length) {
                var s = parseFloat($qtyInputStep.attr('step') || '1');
                var m = parseFloat($qtyInputStep.attr('min') || '0');
                if (isFinite(s) && s > 0) {
                    step = s;
                }
                if (isFinite(m) && m >= 0) {
                    min = m;
                }
            }

            delta = delta - step;
            if (delta < 0) delta = 0;
            setPendingQty(unitId, delta);

            var newQty = baseQty + delta;
            if (newQty < min) {
                newQty = min;
            }
            $row.find('[data-order-existing-qty]').text(formatOrderCurrency(newQty));
            var $qtyInput = $row.find('input[data-order-existing-qty-input]');
            if ($qtyInput.length) {
                $qtyInput.val(newQty);
            }

            var priceRaw = $row.attr('data-price') || '0';
            var price = parseFloat(priceRaw);
            if (!isFinite(price) || price < 0) price = 0;
            var newAmount = newQty * price;
            $row.find('[data-order-existing-amount]').text(formatOrderCurrency(newAmount));

            recalcOrderTotals();
            syncOrderHiddenInputs();
        });

        $(document).on('click', '[data-order-existing-remove]', function () {
            var $row = $(this).closest('[data-order-existing-item]');
            if (!$row.length) return;
            if ($row.attr('data-removed') === '1') return;

            var unitId = String($row.attr('data-product-unit-id') || '');
            var orderItemId = String($row.attr('data-order-item-id') || '');
            if (!unitId || !orderItemId) {
                $row.remove();
                recalcOrderTotals();
                syncOrderHiddenInputs();
                return;
            }

            var baseQtyRaw = $row.attr('data-base-qty') || '0';
            var baseQty = parseFloat(baseQtyRaw);
            if (!isFinite(baseQty) || baseQty < 0) baseQty = 0;

            var priceRaw = $row.attr('data-price') || '0';
            var price = parseFloat(priceRaw);
            if (!isFinite(price) || price < 0) price = 0;

            var baseAmount = baseQty * price;
            if (!isFinite(baseAmount) || baseAmount < 0) baseAmount = 0;

            baseTotal = baseTotal - baseAmount;
            if (!isFinite(baseTotal) || baseTotal < 0) baseTotal = 0;

            pendingItems = $.grep(pendingItems, function (item) {
                return String(item.product_unit_id) !== unitId;
            });

            var exists = false;
            $.each(removedExistingIds, function (index, id) {
                if (id === orderItemId) {
                    exists = true;
                    return false;
                }
            });
            if (!exists) {
                removedExistingIds.push(orderItemId);
            }

            $row.attr('data-removed', '1');
            $row.remove();

            recalcOrderTotals();
            syncOrderHiddenInputs();
        });

        $(document).on('change', 'select[data-order-existing-unit-select]', function () {
            var $select = $(this);
            var $row = $select.closest('[data-order-existing-item]');
            if (!$row.length) return;
            if ($row.attr('data-removed') === '1') return;

            var oldUnitId = String($row.attr('data-product-unit-id') || '');
            var newUnitId = String($select.val() || '');
            if (!newUnitId || newUnitId === oldUnitId) return;

            var unit = unitsById[newUnitId];
            if (!unit) {
                $select.val(oldUnitId);
                return;
            }

            var orderItemId = String($row.attr('data-order-item-id') || '');

            var baseQtyRaw = $row.attr('data-base-qty') || '0';
            var baseQty = parseFloat(baseQtyRaw);
            if (!isFinite(baseQty) || baseQty < 0) baseQty = 0;

            var priceRaw = $row.attr('data-price') || '0';
            var price = parseFloat(priceRaw);
            if (!isFinite(price) || price < 0) price = 0;

            var baseAmount = baseQty * price;
            if (!isFinite(baseAmount) || baseAmount < 0) baseAmount = 0;

            baseTotal = baseTotal - baseAmount;
            if (!isFinite(baseTotal) || baseTotal < 0) baseTotal = 0;

            pendingItems = $.grep(pendingItems, function (item) {
                return String(item.product_unit_id) !== oldUnitId;
            });

            if (orderItemId) {
                var exists = false;
                $.each(removedExistingIds, function (index, id) {
                    if (id === orderItemId) {
                        exists = true;
                        return false;
                    }
                });
                if (!exists) {
                    removedExistingIds.push(orderItemId);
                }
            }

            var qtyInput = $row.find('input[data-order-existing-qty-input]');
            var qtyRaw = qtyInput.length ? qtyInput.val() : '';
            var qty = parseFloat(qtyRaw || '0');
            if (!isFinite(qty) || qty <= 0) {
                qty = baseQty > 0 ? baseQty : 1;
            }

            setPendingQty(unit.id, qty);

            $row.attr('data-removed', '1');
            $row.remove();

            renderNewItems();
            recalcOrderTotals();
            syncOrderHiddenInputs();
        });

        $(document).on('change', 'input[data-order-existing-qty-input]', function () {
            var $input = $(this);
            var $row = $input.closest('[data-order-existing-item]');
            if (!$row.length) return;
            var unitId = String($row.attr('data-product-unit-id') || '');
            if (!unitId) return;

            var baseQtyRaw = $row.attr('data-base-qty') || '0';
            var baseQty = parseFloat(baseQtyRaw);
            if (!isFinite(baseQty) || baseQty < 0) baseQty = 0;

            var step = parseFloat($input.attr('step') || '1');
            var min = parseFloat($input.attr('min') || '0');
            if (!isFinite(step) || step <= 0) {
                step = 1;
            }
            if (!isFinite(min) || min < 0) {
                min = 0;
            }

            var value = parseFloat($input.val() || '0');
            if (!isFinite(value) || value <= 0) {
                value = baseQty > 0 ? baseQty : (min > 0 ? min : step);
            }

            if (value < baseQty) {
                value = baseQty;
            }

            var steps = Math.round(value / step);
            value = steps * step;
            if (value < min) {
                value = min;
            }

            $input.val(value.toFixed(4).replace(/\.?0+$/, ''));

            var delta = value - baseQty;
            if (delta < 0) delta = 0;
            setPendingQty(unitId, delta);

            $row.find('[data-order-existing-qty]').text(formatOrderCurrency(value));

            var priceRaw = $row.attr('data-price') || '0';
            var price = parseFloat(priceRaw);
            if (!isFinite(price) || price < 0) price = 0;
            var newAmount = value * price;
            $row.find('[data-order-existing-amount]').text(formatOrderCurrency(newAmount));

            recalcOrderTotals();
            syncOrderHiddenInputs();
        });

        $(document).on('click', '[data-order-edit-remove]', function () {
            var $row = $(this).closest('[data-order-edit-new-item]');
            if (!$row.length) return;
            var unitId = String($row.attr('data-product-unit-id') || '');
            if (!unitId) return;
            pendingItems = $.grep(pendingItems, function (item) {
                return String(item.product_unit_id) !== unitId;
            });
            renderNewItems();
        });

        $(document).on('click', '[data-order-edit-increase]', function () {
            var $row = $(this).closest('[data-order-edit-new-item]');
            if (!$row.length) return;
            var unitId = $row.attr('data-product-unit-id');
            if (!unitId) return;
            changePendingQty(unitId, 1);
            renderNewItems();
        });

        $(document).on('click', '[data-order-edit-decrease]', function () {
            var $row = $(this).closest('[data-order-edit-new-item]');
            if (!$row.length) return;
            var unitId = $row.attr('data-product-unit-id');
            if (!unitId) return;
            changePendingQty(unitId, -1);
            renderNewItems();
        });

        $(document).on('change', 'input[data-order-edit-qty]', function () {
            var $input = $(this);
            var $row = $input.closest('[data-order-edit-new-item]');
            if (!$row.length) return;
            var unitId = $row.attr('data-product-unit-id');
            if (!unitId) return;
            var step = parseFloat($input.attr('step') || '1');
            var min = parseFloat($input.attr('min') || '1');
            if (!isFinite(step) || step <= 0) {
                step = 1;
            }
            if (!isFinite(min) || min <= 0) {
                min = step;
            }
            var value = parseFloat($input.val() || '0');
            if (!isFinite(value) || value <= 0) {
                value = min;
            }
            var steps = Math.round(value / step);
            value = steps * step;
            if (value < min) {
                value = min;
            }
            $input.val(value.toFixed(4).replace(/\.?0+$/, ''));
            setPendingQty(unitId, value);
            recalcOrderTotals();
            syncOrderHiddenInputs();
        });

        $(document).on('change', 'select[data-order-edit-unit-select]', function () {
            var $select = $(this);
            var $row = $select.closest('[data-order-edit-new-item]');
            if (!$row.length) return;
            var oldUnitId = String($row.attr('data-product-unit-id') || '');
            var newUnitId = String($select.val() || '');
            if (!newUnitId || newUnitId === oldUnitId) return;
            var unit = unitsById[newUnitId];
            if (!unit) return;

            var qty = 0;
            $.each(pendingItems, function (index, item) {
                if (String(item.product_unit_id) === oldUnitId) {
                    var current = parseFloat(item.qty || 0);
                    if (!isFinite(current) || current <= 0) current = 1;
                    qty = current;
                    return false;
                }
            });
            if (qty <= 0) {
                qty = 1;
            }

            pendingItems = $.grep(pendingItems, function (item) {
                return String(item.product_unit_id) !== oldUnitId;
            });
            setPendingQty(unit.id, qty);
            $row.attr('data-product-unit-id', unit.id);
            renderNewItems();
        });

        registerProductSelectorMode('order-edit-add', {
			buildItems: function () {
				var source = window.ORDER_EDIT_PRODUCT_UNITS || [];
				var items = [];
				$.each(source, function (index, row) {
					items.push({
						id: row.id,
						label: row.product_name + ' - ' + row.unit_name,
						sub: row.price_text || '',
						image: row.image_url || null
					});
				});
				return items;
			},
            onConfirm: function (items) {
                if (!items || !items.length) return;
                $.each(items, function (index, item) {
                    if (!item || !item.id) return;
                    var unitId = String(item.id);
                    var unit = unitsById[unitId];
                    if (!unit) return;

                    var step = 1;
                    if (unit.allow_fraction && unit.min_step && unit.min_step > 0) {
                        step = unit.min_step;
                    }

                    var unitPrice = parseFloat(unit.price || unit.price_sell || 0);
                    if (!isFinite(unitPrice) || unitPrice < 0) {
                        unitPrice = 0;
                    }

                    if ($itemsList.length) {
                        var $existingRow = null;
                        $itemsList.find('[data-order-existing-item][data-product-unit-id="' + unitId + '"]').each(function () {
                            var $row = $(this);
                            var priceRawExisting = $row.attr('data-price') || $row.attr('data-base-price') || '0';
                            var priceExisting = parseFloat(priceRawExisting || '0');
                            if (!isFinite(priceExisting) || priceExisting < 0) {
                                priceExisting = 0;
                            }
                            if (Math.abs(priceExisting - unitPrice) < 0.0001) {
                                $existingRow = $row;
                                return false;
                            }
                        });

                        if ($existingRow && $existingRow.length) {
                            var baseQtyRaw = $existingRow.attr('data-base-qty') || '0';
                            var baseQty = parseFloat(baseQtyRaw || '0');
                            if (!isFinite(baseQty) || baseQty < 0) {
                                baseQty = 0;
                            }

                            var delta = 0;
                            $.each(pendingItems, function (i, row) {
                                if (String(row.product_unit_id) === unitId) {
                                    var current = parseFloat(row.qty || 0);
                                    if (!isFinite(current) || current < 0) {
                                        current = 0;
                                    }
                                    delta = current;
                                    return false;
                                }
                            });

                            delta = delta + step;
                            setPendingQty(unitId, delta);

                            var newQty = baseQty + delta;
                            $existingRow.find('[data-order-existing-qty]').text(formatOrderCurrency(newQty));
                            var $qtyInputExisting = $existingRow.find('input[data-order-existing-qty-input]');
                            if ($qtyInputExisting.length) {
                                $qtyInputExisting.val(newQty);
                            }

                            var priceRawRow = $existingRow.attr('data-price') || $existingRow.attr('data-base-price') || '0';
                            var priceRow = parseFloat(priceRawRow || '0');
                            if (!isFinite(priceRow) || priceRow < 0) {
                                priceRow = 0;
                            }
                            var newAmountExisting = newQty * priceRow;
                            $existingRow.find('[data-order-existing-amount]').text(formatOrderCurrency(newAmountExisting));

                            recalcOrderTotals();
                            syncOrderHiddenInputs();

                            return;
                        }
                    }

                    var found = false;
                    $.each(pendingItems, function (i, row) {
                        if (String(row.product_unit_id) === unitId) {
                            var currentQty = parseFloat(row.qty || 0);
                            if (!isFinite(currentQty) || currentQty < 0) {
                                currentQty = 0;
                            }
                            row.qty = currentQty + step;
                            if (!row.mode) {
                                row.mode = 'new';
                            }
                            found = true;
                            return false;
                        }
                    });

                    if (!found) {
                        var firstQty = step;
                        pendingItems.push({
                            product_unit_id: unit.id,
                            qty: firstQty,
                            mode: 'new'
                        });
                    }
                });

                renderNewItems();
            }
        });

        recalcOrderTotals();
    }

    function initOrderFilterDateRange() {
        var $forms = $('form[data-order-filter-form]');
        if (!$forms.length) return;

        $forms.each(function () {
            var $form = $(this);
            var $input = $form.find('[data-order-date-range]');
            var $from = $form.find('[data-order-date-from]');
            var $to = $form.find('[data-order-date-to]');

            if (!$input.length || !$from.length || !$to.length) return;

			if (window.flatpickr) {
				window.flatpickr($input[0], {
					mode: 'range',
					dateFormat: 'Y-m-d',
					allowInput: true,
					locale: {
						rangeSeparator: ' - '
					}
				});
			}

            $form.on('submit', function () {
                var raw = $.trim($input.val() || '');
                if (!raw) {
                    $from.val('');
                    $to.val('');
                    // Không gửi date_range khi không có giá trị
                    $input.prop('name', '');
                    return;
                }

                var parts = raw.split(' - ');
                var from = $.trim(parts[0] || '');
                var to = $.trim(parts[1] || '');

                function isValidDate(value) {
                    return /^\d{4}-\d{2}-\d{2}$/.test(value);
                }

                if (!isValidDate(from)) {
                    from = '';
                }
                if (!isValidDate(to)) {
                    to = '';
                }

                if (!from && !to) {
                    $from.val('');
                    $to.val('');
                    $input.prop('name', '');
                    return;
                }

                $from.val(from);
                $to.val(to);
                // Đã sync sang from_date/to_date, không cần gửi date_range nữa
                $input.prop('name', '');
            });
        });
    }

    function initCustomerOrderSelection() {
        var $list = $('[data-customer-order-list]');
        if (!$list.length) return;

        var $panel = $('[data-customer-order-selection-panel]');
        var $countEl = $panel.find('[data-customer-selected-count]');
        var $totalEl = $panel.find('[data-customer-selected-total]');
        var $paidEl = $panel.find('[data-customer-selected-paid]');
        var $debtEl = $panel.find('[data-customer-selected-debt]');
        var $clearBtn = $panel.find('[data-customer-selection-clear]');
        var $toggleBtn = $('[data-customer-selection-toggle]');

        var selectMode = false;

        function formatMoney(number) {
            number = number || 0;
            if (typeof formatCurrency === 'function') {
                return formatCurrency(number) + ' đ';
            }
            try {
                return number.toLocaleString('vi-VN') + ' đ';
            } catch (e) {
                return number + ' đ';
            }
        }

        function updateSummary() {
            var sumTotal = 0;
            var sumPaid = 0;
            var count = 0;

            $list.find('[data-customer-order-item][data-selected="1"]').each(function () {
                var $item = $(this);
                var total = parseFloat($item.attr('data-order-total') || '0') || 0;
                var paid = parseFloat($item.attr('data-order-paid') || '0') || 0;
                sumTotal += total;
                sumPaid += paid;
                count += 1;
            });

            var sumDebt = sumTotal - sumPaid;
            if (sumDebt < 0) {
                sumDebt = 0;
            }

            if ($countEl.length) {
                $countEl.text(count);
            }
            if ($totalEl.length) {
                $totalEl.text(formatMoney(sumTotal));
            }
            if ($paidEl.length) {
                $paidEl.text(formatMoney(sumPaid));
            }
            if ($debtEl.length) {
                $debtEl.text(formatMoney(sumDebt));
            }
        }

        function clearSelection() {
            $list.find('[data-customer-order-item][data-selected="1"]').each(function () {
                var $item = $(this);
                $item.attr('data-selected', '0');
                $item.removeClass('ring-emerald-200 bg-emerald-50');
                $item.addClass('bg-white');
            });
            selectMode = false;
            $panel.addClass('hidden');
            $list.removeAttr('data-customer-order-select-mode');
            if ($countEl.length) {
                $countEl.text('0');
            }
            if ($totalEl.length) {
                $totalEl.text(formatMoney(0));
            }
            if ($paidEl.length) {
                $paidEl.text(formatMoney(0));
            }
            if ($debtEl.length) {
                $debtEl.text(formatMoney(0));
            }
        }

        function toggleItem($item) {
            var selected = $item.attr('data-selected') === '1';
            if (selected) {
                $item.attr('data-selected', '0');
                $item.removeClass('ring-emerald-200 bg-emerald-50');
                $item.addClass('bg-white');
            } else {
                $item.attr('data-selected', '1');
                $item.removeClass('bg-white');
                $item.addClass('ring-emerald-200 bg-emerald-50');
            }
            updateSummary();
        }

        $list.on('click', '[data-customer-order-item]', function (e) {
            if (!selectMode) {
                return;
            }
            e.preventDefault();
            toggleItem($(this));
        });

        if ($clearBtn.length) {
            $clearBtn.on('click', function (e) {
                e.preventDefault();
                clearSelection();
            });
        }

        if ($toggleBtn.length) {
            $toggleBtn.on('click', function (e) {
                e.preventDefault();
                if (!selectMode) {
                    selectMode = true;
                    $panel.removeClass('hidden');
                    $list.attr('data-customer-order-select-mode', '1');
                    updateSummary();
                }
            });
        }
    }

    function initInfiniteScroll() {
        var $lists = $('[data-infinite-list][data-infinite-url]');
        if (!$lists.length) return;

        $lists.each(function () {
            var $list = $(this);
            if ($list.attr('data-infinite-initialized') === '1') {
                return;
            }
            $list.attr('data-infinite-initialized', '1');
            var baseUrl = $list.attr('data-infinite-url') || '';
            if (!baseUrl) return;

            var currentPage = parseInt($list.attr('data-current-page') || $list.data('current-page') || '1', 10);
            if (!currentPage || currentPage < 1) {
                currentPage = 1;
            }

            function parseHasMore($source) {
                var value = $source && ($source.attr('data-has-more') || $source.data('has-more'));
                if (value === undefined || value === null) {
                    return false;
                }
                if (typeof value === 'string') {
                    return value === '1' || value.toLowerCase() === 'true';
                }
                return !!value;
            }

            var hasMore = parseHasMore($list);
            var extraQuery = $list.attr('data-infinite-query') || $list.data('infinite-query') || '';
            if (!hasMore) {
                return;
            }

            var $sentinel = $('<div>', {
                'data-infinite-sentinel': '1',
                "class": 'py-3 text-center text-sm text-slate-400'
            }).text('Đang tải thêm...');

            $list.after($sentinel);

            var observer = null;
            var onScroll = null;
            var loading = false;

            function cleanup() {
                if ($sentinel) {
                    $sentinel.remove();
                    $sentinel = null;
                }
                $list.removeAttr('data-infinite-initialized');
                if (observer) {
                    observer.disconnect();
                    observer = null;
                }
                if (onScroll) {
                    $(window).off('scroll', onScroll);
                    onScroll = null;
                }
            }

            function buildUrl(page) {
                var url = baseUrl;
                var parts = [];
                if (extraQuery) {
                    parts.push(extraQuery);
                }
                parts.push('page=' + page);
                parts.push('ajax=1');
                var query = parts.join('&');
                var separator = url.indexOf('?') === -1 ? '?' : '&';
                url += separator + query;
                return url;
            }

            function loadNextPage() {
                if (loading || !hasMore || !$sentinel) {
                    return;
                }
                loading = true;

                var nextPage = currentPage + 1;
                var url = buildUrl(nextPage);

                $.ajax({
                    url: url,
                    method: 'GET',
                    dataType: 'html',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).done(function (html) {
                    if (!html) {
                        hasMore = false;
                        cleanup();
                        return;
                    }

                    var $wrapper = $('<div>').html(html);
                    var $newList = $wrapper.find('[data-infinite-list]').first();
                    var $itemsSource = $newList.length ? $newList : $wrapper;
                    var $newItems = $itemsSource.find('[data-infinite-item]');

                    if (!$newItems.length) {
                        hasMore = false;
                        cleanup();
                        return;
                    }

                    $newItems.appendTo($list);

                    hasMore = $newList.length ? parseHasMore($newList) : false;
                    currentPage = nextPage;
                    $list.attr('data-current-page', String(currentPage));
                    $list.attr('data-has-more', hasMore ? '1' : '0');

                    if (!hasMore) {
                        cleanup();
                    }
                }).fail(function () {
                    hasMore = false;
                    cleanup();
                }).always(function () {
                    loading = false;
                });
            }

            if ('IntersectionObserver' in window) {
                observer = new IntersectionObserver(function (entries) {
                    for (var i = 0; i < entries.length; i++) {
                        var entry = entries[i];
                        if (entry.isIntersecting) {
                            loadNextPage();
                            break;
                        }
                    }
                });
                observer.observe($sentinel.get(0));
            } else {
                onScroll = function () {
                    if (!$sentinel) return;
                    var scrollBottom = $(window).scrollTop() + $(window).height();
                    var sentinelTop = $sentinel.offset().top;
                    if (scrollBottom + 100 >= sentinelTop) {
                        loadNextPage();
                    }
                };
                $(window).on('scroll', onScroll);
            }
        });
    }

    window.APP_initInfiniteScroll = initInfiniteScroll;

    function initFloatingFormActions() {
        var containers = document.querySelectorAll('[data-floating-actions]');
        if (!containers.length) {
            return;
        }
        var supportsIO = 'IntersectionObserver' in window;
        containers.forEach(function (container) {
            var form = container.closest('form');
            if (!form) {
                return;
            }
            if (form.dataset.floatingInit === '1') {
                return;
            }
            form.dataset.floatingInit = '1';
            var primary = container.querySelector('[data-floating-primary]');
            if (!primary) {
                primary = container.querySelector('button[type="submit"], input[type="submit"]');
            }
            if (!primary) {
                return;
            }
            var labelText = primary.textContent || '';
            labelText = labelText.trim();
            if (!labelText) {
                labelText = 'Lưu';
            }
            var iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661Z" /></svg>';
            function escapeHtml(str) {
                return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            }
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('data-floating-save', '1');
            btn.setAttribute('aria-label', labelText);
            btn.className = 'fixed bottom-[4.3rem] right-3 z-19 inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500';
            btn.innerHTML = '<span>' + escapeHtml(labelText) + '</span>';
            btn.classList.add('hidden');
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                if (primary.disabled) {
                    return;
                }
                primary.click();
            });
            document.body.appendChild(btn);
            function updateVisibility(visible) {
                if (visible) {
                    btn.classList.add('hidden');
                } else {
                    btn.classList.remove('hidden');
                }
            }
            if (supportsIO) {
                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        updateVisibility(entry.isIntersecting);
                    });
                }, {
                    root: null,
                    rootMargin: '0px 0px -40px 0px',
                    threshold: 0
                });
                observer.observe(container);
            } else {
                function onScroll() {
                    var rect = container.getBoundingClientRect();
                    var viewHeight = window.innerHeight || document.documentElement.clientHeight;
                    var isVisible = rect.bottom > 0 && rect.top < viewHeight;
                    updateVisibility(isVisible);
                }
                window.addEventListener('scroll', onScroll);
                window.addEventListener('resize', onScroll);
                onScroll();
            }
        });
    }

    function initStickyListHeaders() {
        var forms = document.querySelectorAll('form[data-list-sticky]');
        if (!forms.length) {
            return;
        }
        var items = [];
        forms.forEach(function (el) {
            var stickyTop = 0;
            try {
                var computedStyle = window.getComputedStyle(el);
                if (computedStyle && computedStyle.top) {
                    var parsedTop = parseInt(computedStyle.top, 10);
                    if (!isNaN(parsedTop)) {
                        stickyTop = parsedTop;
                    }
                }
            } catch (e) {
                stickyTop = 0;
            }
            items.push({
                el: el,
                stickyTop: stickyTop
            });
        });
        if (!items.length) {
            return;
        }
        function updateAllSticky() {
            items.forEach(function (item) {
                var rect = item.el.getBoundingClientRect();
                var isStuck = rect.top <= item.stickyTop;
                if (isStuck) {
                    item.el.classList.add('bg-white', 'border-b', 'border-slate-200');
                    item.el.classList.remove('border-transparent');
                } else {
                    item.el.classList.remove('bg-white', 'border-b', 'border-slate-200');
                    item.el.classList.add('border-transparent');
                }
            });
        }
        updateAllSticky();
        window.addEventListener('scroll', updateAllSticky);
        window.addEventListener('load', updateAllSticky);
        window.addEventListener('pageshow', function (e) {
            var ev = e || window.event;
            if (ev && ev.persisted) {
                updateAllSticky();
            }
        });
    }

    $(function () {
        initLoadingButtons();
        var $body = $('body');
        var appBasePath = $body.data('base-path') || '';
        window.APP_BASE_PATH = appBasePath;

        var flashMessage = $body.data('flash-message');
        if (flashMessage) {
            var flashType = $body.data('flash-type') || 'info';
            window.showToast(flashType, flashMessage);
        }

        initSelect2();
        initUnitRows();
        initPurchaseItemRows();
        initProductList();
        initImageUpload();
		initProductInventoryUnit();
        initAppMenu();
        initMigration(appBasePath);
        initProductSelector();
        initPos();
        initOrderEdit();
        initOrderAdd();
		initOrderReturn();
        if (typeof initOrderDiscountModal === 'function') {
            initOrderDiscountModal();
        }
        initOrderPaymentModal();
        initPurchasePaymentModal();
        initPurchasePaymentFields();
		initHeaderActionsMenu();
        initOrderFilterDateRange();
        initCustomerOrderSelection();
        initInfiniteScroll();
        initMoneyInputs();
        if (typeof initOrderSurchargeModal === 'function') {
            initOrderSurchargeModal();
        }
        initFloatingFormActions();
        initStickyListHeaders();
    });
})(jQuery);
