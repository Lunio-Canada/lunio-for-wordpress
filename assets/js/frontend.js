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
            showError('An error occurred.');
        });
    });
    function showResult(result) {
        if (!result.data) {
            showError('Invalid response data.');
            return;
        }
        let d = result.data;
        let html = '<h3>Tax Calculation</h3>';
        html += '<p>Province: ' + d.province_code + '</p>';
        html += '<p>Subtotal: $' + parseFloat(d.subtotal).toFixed(2) + '</p>';
        if (d.tax.gst > 0) html += '<p>GST: $' + parseFloat(d.tax.gst).toFixed(2) + '</p>';
        if (d.tax.hst > 0) html += '<p>HST: $' + parseFloat(d.tax.hst).toFixed(2) + '</p>';
        if (d.tax.pst > 0) html += '<p>PST: $' + parseFloat(d.tax.pst).toFixed(2) + '</p>';
        if (d.tax.qst > 0) html += '<p>QST: $' + parseFloat(d.tax.qst).toFixed(2) + '</p>';
        html += '<p>Total Tax: $' + parseFloat(d.tax.total_tax).toFixed(2) + '</p>';
        html += '<p>Total: $' + parseFloat(d.total).toFixed(2) + '</p>';
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