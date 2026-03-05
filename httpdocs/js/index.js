document.addEventListener('DOMContentLoaded', () => {
    const cartForms = document.querySelectorAll('form[action="cart_action.php"]');

    cartForms.forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('ajax', '1');

            fetch('cart_action.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update badge
                        const badge = document.querySelector('.cart-badge');
                        if (badge) {
                            badge.textContent = data.cart_count;

                            // Add a small animation effect
                            badge.style.transform = 'scale(1.5)';
                            badge.style.transition = 'transform 0.2s';
                            setTimeout(() => {
                                badge.style.transform = 'scale(1)';
                            }, 200);
                        }

                        // Show or update the floating cart button
                        let floatingBtn = document.querySelector('.btn-accent[href="cart.php"]');
                        if (floatingBtn) {
                            floatingBtn.innerHTML = `🛒 Procedi all'Ordine (${data.cart_count})`;
                        } else if (data.cart_count > 0) {
                            // Create floating button if it doesn't exist yet
                            const container = document.createElement('div');
                            container.id = 'floating-cart-btn';
                            container.style.position = 'fixed';
                            container.style.bottom = '20px';
                            container.style.right = '20px';
                            container.style.zIndex = '1000';

                            container.innerHTML = `
                            <a href="cart.php" class="btn btn-accent" style="box-shadow: 0 4px 10px rgba(0,0,0,0.5); font-size: 1.2rem; border-radius: 50px; padding: 15px 30px; display: flex; align-items: center; gap: 8px;">
                                🛒 Procedi all'Ordine (<span class="floating-btn-count">${data.cart_count}</span>)
                            </a>
                        `;
                            document.body.appendChild(container); // Append to body
                        } else {
                            // Update existing floating button if it had a count span
                            let floatingCount = document.querySelector('.floating-btn-count');
                            if (floatingCount) {
                                floatingCount.textContent = data.cart_count;
                            }
                        }
                    }
                })
                .catch(error => console.error('Error adding to cart:', error));
        });
    });
});
