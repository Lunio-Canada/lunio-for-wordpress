document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('lunio-tax-form');
    if (!form) return;
    const calculator = form.closest('.lunio-tax-calculator');
    const resultDiv = document.getElementById('lunio-result');
    const errorDiv = document.getElementById('lunio-error');
    const btn = document.getElementById('lunio-calculate-btn');
    const showBreakdown = calculator.dataset.showBreakdown === 'true';
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const amount = document.getElementById('lunio-amount').value.trim();
        const province = document.getElementById('lunio-province').value;
        const amt = parseFloat(amount);
        if (!amount || isNaN(amt) || amt <= 0) {
            showError('Please enter a valid amount greater than 0.');
            return;
        }
        if (!province) {
            showError('Please select a province.');
            return;
        }
        btn.disabled = true;
        btn.textContent = 'Calculating...';
        hideResult();
        hideError();
        const data = new FormData();
        data.append('action', 'lunio_calculate_tax');
        data.append('nonce', lunioAjax.nonce);
        data.append('amount', amount);
        data.append('province_code', province);
        fetch(lunioAjax.ajaxurl, {
            method: 'POST',
            body: data
        })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            btn.textContent = 'Calculate Tax';
            if (data.success && data.data && data.data.result && data.data.result.success === true && data.data.result.data) {
                showResult(data.data.result);
            } else if (data.success === false && data.data && data.data.message) {
                showError(data.data.message);
            } else {
                showError('Unexpected response format.');
            }
        })
        .catch(error => {
            btn.disabled = false;
            btn.textContent = 'Calculate Tax';
            showError('Network error. Please try again.');
        });
    });
    function showResult(result) {
        if (!result.data) {
            showError('Invalid response data.');
            return;
        }
        let d = result.data;
        let html = '<div class="lunio-result-header">Tax Calculation for ' + d.province_code + '</div>';
        html += '<div class="lunio-result-row"><span>Subtotal:</span><span>$' + parseFloat(d.subtotal).toFixed(2) + '</span></div>';
        if (showBreakdown && d.tax.gst > 0) html += '<div class="lunio-result-row"><span>GST:</span><span>$' + parseFloat(d.tax.gst).toFixed(2) + '</span></div>';
        if (showBreakdown && d.tax.hst > 0) html += '<div class="lunio-result-row"><span>HST:</span><span>$' + parseFloat(d.tax.hst).toFixed(2) + '</span></div>';
        if (showBreakdown && d.tax.pst > 0) html += '<div class="lunio-result-row"><span>PST:</span><span>$' + parseFloat(d.tax.pst).toFixed(2) + '</span></div>';
        if (showBreakdown && d.tax.qst > 0) html += '<div class="lunio-result-row"><span>QST:</span><span>$' + parseFloat(d.tax.qst).toFixed(2) + '</span></div>';
        html += '<div class="lunio-result-row lunio-total-tax"><span>Total Tax:</span><span>$' + parseFloat(d.tax.total_tax).toFixed(2) + '</span></div>';
        html += '<div class="lunio-result-row lunio-grand-total"><span>Total:</span><span>$' + parseFloat(d.total).toFixed(2) + '</span></div>';
        resultDiv.innerHTML = html;
        resultDiv.style.display = 'block';
    }
    function showError(message) {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
    }
    function hideResult() {
        resultDiv.style.display = 'none';
    }
    function hideError() {
        errorDiv.style.display = 'none';
    }
});