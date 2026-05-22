let cart = JSON.parse(localStorage.getItem('efootball_cart') || '[]');

function syncCart() {
    cart = JSON.parse(localStorage.getItem('efootball_cart') || '[]');
    return cart;
}

function addArticleToCart(article) {
    syncCart();
    const item = {
        id: article.id,
        title: article.title,
        price: parseFloat(article.price)
    };
    cart.push(item);
    updateCart();
    showNotification('Article ajoute au panier.');
}

function updateCart() {
    localStorage.setItem('efootball_cart', JSON.stringify(cart));
    const el = document.getElementById('cartCount');
    if (el) {
        el.textContent = cart.length;
    }
}

function showNotification(message) {
    const n = document.createElement('div');
    n.textContent = message;
    n.style.position = 'fixed';
    n.style.right = '20px';
    n.style.bottom = '20px';
    n.style.background = 'linear-gradient(45deg,#00d4ff,#00ff88)';
    n.style.color = '#042';
    n.style.padding = '10px 14px';
    n.style.borderRadius = '8px';
    n.style.boxShadow = '0 6px 20px rgba(0,0,0,0.2)';
    n.style.zIndex = 3000;
    document.body.appendChild(n);
    setTimeout(() => { n.style.opacity = '0'; n.style.transition = 'opacity 400ms'; }, 1400);
    setTimeout(() => n.remove(), 2000);
}

async function openCart() {
    syncCart();
    const modal = document.getElementById('cartModal');
    const cartItems = document.getElementById('cartItems');
    if (!modal || !cartItems) {
        return;
    }

    if (cart.length === 0) {
        cartItems.innerHTML = '<p style="text-align: center; padding: 40px; opacity: 0.7;">Votre panier est vide</p>';
    } else {
        cartItems.innerHTML = cart.map((it, idx) => `
            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.03)">
                <div>
                    <div style="font-weight:600">${it.title}</div>
                </div>
                <div style="text-align:right">
                    <div style="font-weight:700">${it.price} FCFA</div>
                    <button onclick="removeFromCart(${idx})" style="margin-top:6px;padding:6px 8px;border-radius:6px;background:#ff4d6d;color:#fff;border:none">Supprimer</button>
                </div>
            </div>
        `).join('');
    }
    
    // Initialiser le formulaire de profil
    if (typeof initProfileForm === 'function') {
        await initProfileForm();
    }
    
    modal.classList.add('open');
}

function closeCart() {
    const modal = document.getElementById('cartModal');
    if (modal) {
        modal.classList.remove('open');
    }
}

function removeFromCart(index) {
    syncCart();
    cart.splice(index, 1);
    updateCart();
    openCart();
}

async function checkout() {
    syncCart();
    if (cart.length === 0) {
        alert('Votre panier est vide.');
        return;
    }

    // Valider et sauvegarder le profil si le formulaire est visible
    if (typeof validateProfileForm === 'function' && !validateProfileForm()) {
        return;
    }

    // Sauvegarder le profil si le formulaire est visible
    const profileFormSection = document.getElementById('profileFormSection');
    if (profileFormSection && profileFormSection.style.display !== 'none') {
        if (typeof getProfileFormData === 'function') {
            const profileData = getProfileFormData();
            if (profileData) {
                if (typeof saveProfileData === 'function') {
                    console.log('Tentative de sauvegarde du profil...', profileData);
                    const saved = await saveProfileData(profileData);
                    if (!saved) {
                        alert('Erreur lors de la sauvegarde de votre profil. Veuillez verifier les champs et reessayer.');
                        return;
                    }
                    console.log('Profil sauvegarde avec succes.');
                }
            }
        }
    }

    const payerPhoneNumber = document.getElementById('payerPhoneNumber');
    const rawPhone = payerPhoneNumber ? payerPhoneNumber.value.replace(/\s/g, '') : '';
    if (!/^\d{9}$/.test(rawPhone)) {
        alert('Veuillez entrer un numero de telephone senegalais valide (9 chiffres).');
        return;
    }

    const checkoutButton = document.querySelector('#cartModal .cta-button');
    const originalButtonText = checkoutButton ? checkoutButton.textContent : '';
    if (checkoutButton) {
        checkoutButton.disabled = true;
        checkoutButton.textContent = 'Envoi de la demande...';
    }

    const payload = [...cart, { payerPhoneNumber: rawPhone }];

    fetch('checkout.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                cart = [];
                updateCart();
                closeCart();
                if (data.whatsappUrl) {
                    window.open(data.whatsappUrl, '_blank', 'noopener');
                }
                alert(data.message || 'Commande enregistree avec succes !');
                setTimeout(() => { window.location.href = 'order_history.php'; }, 2000);
                return;
            }

            if (data.redirect_url) {
                alert(data.message || 'Completez votre profil avant de commander.');
                window.location.href = data.redirect_url;
                return;
            }

            alert('Erreur: ' + (data.message || 'Reponse invalide du serveur.'));
            if (checkoutButton) {
                checkoutButton.disabled = false;
                checkoutButton.textContent = originalButtonText;
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur technique est survenue. Veuillez reessayer.');
            if (checkoutButton) {
                checkoutButton.disabled = false;
                checkoutButton.textContent = originalButtonText;
            }
        });
}

document.addEventListener('DOMContentLoaded', updateCart);
