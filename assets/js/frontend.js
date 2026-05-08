document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('lunio-tax-form');
    if (!form) return;
    const resultDiv = document.getElementById('lunio-result');
    const errorDiv = document.getElementById('lunio-error');
    const btn = document.getElementById('lunio-calculate-btn');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const amount = document.getElementById('lunio-amount').value;
        const province = document.getElementById('lunio-province').value;
        if (!amount || !province) {
            showError('Please fill in all fields.');
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
            if (data.success) {
                showResult(data.data.result);
            } else {
                showError(data.data.message);
            }
        })
        .catch(error => {
            btn.disabled = false;
            btn.textContent = 'Calculate Tax';
            showError('An error occurred.');
        });
    });
    function showResult(result) {
        let html = '<h3>Tax Calculation</h3>';
        html += '<p>Subtotal: $' + result.subtotal.toFixed(2) + '</p>';
        if (result.gst > 0) html += '<p>GST: $' + result.gst.toFixed(2) + '</p>';
        if (result.hst > 0) html += '<p>HST: $' + result.hst.toFixed(2) + '</p>';
        if (result.pst > 0) html += '<p>PST: $' + result.pst.toFixed(2) + '</p>';
        if (result.qst > 0) html += '<p>QST: $' + result.qst.toFixed(2) + '</p>';
        html += '<p>Total Tax: $' + result.total_tax.toFixed(2) + '</p>';
        html += '<p>Total: $' + result.total.toFixed(2) + '</p>';
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