/**
 * Article Lightbox Modal
 * Gere l affichage, la navigation et les interactions de la lightbox
 */

class ArticleLightbox {
    constructor() {
        this.modal = null;
        this.currentArticleId = null;
        this.currentArticlePrice = 0;
        this.currentGalleryIndex = 0;
        this.galleryImages = [];
        this.isLoading = false;
        this.currentArticleStatus = 'available';
        this.currentArticleFavorite = false;

        const path = window.location.pathname;
        this.baseUrl = path.includes('/admin/') ? '../' : '';

        this.init();
    }

    init() {
        if (!document.getElementById('articleModal')) {
            this.createModal();
        }

        this.modal = document.getElementById('articleModal');
        this.attachEvents();
    }

    createModal() {
        const modalHTML = `
            <div class="article-modal" id="articleModal">
                <div class="article-lightbox" id="lightboxContent">
                    <div class="lightbox-gallery" id="galleryContainer">
                        <div class="gallery-stage">
                            <div class="gallery-main" id="galleryMain">
                                <img id="galleryImg" src="" alt="Article image">
                            </div>

                            <div class="gallery-nav">
                                <button class="gallery-btn" id="prevBtn" onclick="articleLightbox.prevImage()" title="Precedent">&#8249;</button>
                                <button class="gallery-btn" id="nextBtn" onclick="articleLightbox.nextImage()" title="Suivant">&#8250;</button>
                            </div>

                            <div class="gallery-indicator" id="galleryIndicator">1/1</div>
                        </div>

                        <div class="gallery-sidebar" id="galleryThumbs"></div>
                    </div>

                    <div class="lightbox-details" id="detailsContainer">
                        <button class="lightbox-close" id="closeBtn" onclick="articleLightbox.close()">×</button>

                        <div class="lightbox-header">
                            <div class="lightbox-topline">
                                <h2 class="lightbox-title" id="articleTitle">Chargement...</h2>
                                <div class="lightbox-actions">
                                    <span class="lightbox-status status-available" id="articleStatus">Disponible</span>
                                    <button class="lightbox-favorite-btn" id="lightboxFavoriteBtn" type="button" onclick="articleLightbox.toggleFavorite()" aria-label="Ajouter aux favoris">♥</button>
                                </div>
                            </div>
                            <div class="lightbox-status-copy" id="articleStatusCopy"></div>
                            <div>
                                <div class="price-label">Prix</div>
                                <div class="lightbox-price" id="articlePrice">---</div>
                            </div>
                        </div>

                        <div class="lightbox-info-grid">
                            <div class="info-item">
                                <div class="info-label">Plateforme</div>
                                <div class="info-value" id="infoPlatform">---</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Delai de livraison</div>
                                <div class="info-value" id="infoDelivery">---</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Statut de liaison</div>
                                <div class="info-value" id="infoBinding">---</div>
                            </div>
                        </div>

                        <div id="whyChooseContainer"></div>
                        <p class="lightbox-description" id="articleDescription">---</p>

                        <button class="lightbox-buy-btn" onclick="articleLightbox.addToCart()">
                            Acheter maintenant
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }

    attachEvents() {
        this.modal.addEventListener('click', (event) => {
            if (event.target === this.modal) {
                this.close();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (!this.modal.classList.contains('open')) {
                return;
            }

            if (event.key === 'Escape') this.close();
            if (event.key === 'ArrowLeft') this.prevImage();
            if (event.key === 'ArrowRight') this.nextImage();
        });
    }

    open(articleId) {
        if (this.isLoading) {
            return;
        }

        this.isLoading = true;
        this.currentArticleId = articleId;
        this.currentGalleryIndex = 0;
        this.modal.classList.add('open');

        fetch(`${this.baseUrl}article_api.php?id=${articleId}`)
            .then((response) => {
                if (!response.ok) {
                    throw new Error('Article non trouve');
                }
                return response.json();
            })
            .then((article) => {
                this.displayArticle(article);
                this.isLoading = false;
            })
            .catch((error) => {
                console.error('Erreur:', error);
                document.getElementById('galleryImg').src = '';
                document.getElementById('articleTitle').textContent = 'Erreur';
                document.getElementById('articleDescription').textContent = 'Impossible de charger cet article.';
                if (typeof window.notify === 'function') {
                    window.notify('Impossible de charger cet article pour le moment.', 'error', 'Chargement echoue');
                }
                this.isLoading = false;
            });
    }

    displayArticle(article) {
        this.galleryImages = article.gallery_images && article.gallery_images.length > 0
            ? article.gallery_images
            : [article.image];

        this.currentArticleId = article.id;
        this.currentArticlePrice = parseFloat(article.price) || 0;
        this.currentArticleStatus = article.status_meta?.value || 'available';
        this.currentArticleFavorite = !!article.is_favorite;

        document.getElementById('articleTitle').textContent = article.title;
        document.getElementById('articlePrice').textContent = new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'XOF',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(this.currentArticlePrice);

        document.getElementById('infoPlatform').textContent = article.platform || '---';
        document.getElementById('infoDelivery').textContent = article.delivery_time || '---';
        document.getElementById('infoBinding').textContent = article.binding_status || '---';
        document.getElementById('articleDescription').textContent = article.content ? article.content.replace(/<[^>]*>/g, '') : '';
        this.updateStatus(article.status_meta || { value: 'available', label: 'Disponible', class: 'status-available' });
        this.updateFavoriteButton();

        this.displayWhyChoose(article.why_choose_us || []);
        this.renderThumbnails();
        this.showImage(0);

        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const thumbs = document.getElementById('galleryThumbs');
        const hasMultiple = this.galleryImages.length > 1;

        prevBtn.style.display = hasMultiple ? 'flex' : 'none';
        nextBtn.style.display = hasMultiple ? 'flex' : 'none';
        thumbs.style.display = hasMultiple ? '' : 'none';
    }

    displayWhyChoose(items) {
        const container = document.getElementById('whyChooseContainer');

        if (!items || items.length === 0) {
            container.innerHTML = '';
            return;
        }

        container.innerHTML = `
            <div class="why-choose-section">
                <div class="why-choose-title">Pourquoi nous choisir</div>
                <ul class="why-choose-list">
                    ${items.map((item) => `<li class="why-choose-item">${item}</li>`).join('')}
                </ul>
            </div>
        `;
    }

    getImagePath(imagePath) {
        if (!imagePath) {
            return '';
        }

        return imagePath.startsWith('http') || imagePath.startsWith('/')
            ? imagePath
            : `${this.baseUrl}uploads/articles/${imagePath}`;
    }

    renderThumbnails() {
        const thumbs = document.getElementById('galleryThumbs');
        if (!thumbs) {
            return;
        }

        thumbs.innerHTML = this.galleryImages.map((imagePath, index) => `
            <button class="gallery-thumb${index === this.currentGalleryIndex ? ' active' : ''}" type="button" data-index="${index}" aria-label="Voir image ${index + 1}">
                <img src="${this.getImagePath(imagePath)}" alt="Miniature ${index + 1}">
            </button>
        `).join('');

        thumbs.querySelectorAll('.gallery-thumb').forEach((thumb) => {
            thumb.addEventListener('click', () => {
                this.showImage(parseInt(thumb.dataset.index, 10));
            });
        });
    }

    updateThumbnailState() {
        document.querySelectorAll('#galleryThumbs .gallery-thumb').forEach((thumb, index) => {
            thumb.classList.toggle('active', index === this.currentGalleryIndex);
        });
    }

    showImage(index) {
        if (this.galleryImages.length === 0) {
            return;
        }

        if (index >= this.galleryImages.length) index = 0;
        if (index < 0) index = this.galleryImages.length - 1;

        this.currentGalleryIndex = index;

        const img = document.getElementById('galleryImg');
        img.src = this.getImagePath(this.galleryImages[index]);

        document.getElementById('galleryIndicator').textContent = `${index + 1}/${this.galleryImages.length}`;
        this.updateThumbnailState();
    }

    prevImage() {
        this.showImage(this.currentGalleryIndex - 1);
    }

    nextImage() {
        this.showImage(this.currentGalleryIndex + 1);
    }

    close() {
        this.modal.classList.remove('open');
        this.currentArticleId = null;
        this.currentGalleryIndex = 0;
        this.galleryImages = [];
        this.currentArticleStatus = 'available';
        this.currentArticleFavorite = false;
    }

    addToCart() {
        if (!this.currentArticleId) {
            return;
        }

        if (this.currentArticleStatus !== 'available') {
            showNotification('Cet article n est pas disponible a la commande pour le moment.', 'warning', 'Stock');
            return;
        }

        const cart = JSON.parse(localStorage.getItem('efootball_cart') || '[]');

        if (cart.find((item) => item.id === this.currentArticleId)) {
            showNotification('Article deja dans le panier !', 'warning', 'Panier');
            this.close();
            return;
        }

        cart.push({
            id: this.currentArticleId,
            title: document.getElementById('articleTitle').textContent,
            price: this.currentArticlePrice
        });

        localStorage.setItem('efootball_cart', JSON.stringify(cart));
        updateCartCount();
        this.close();
        showNotification('Article ajoute au panier !', 'success', 'Panier');
    }

    updateStatus(statusMeta) {
        const statusNode = document.getElementById('articleStatus');
        const copyNode = document.getElementById('articleStatusCopy');
        const buttonNode = document.querySelector('.lightbox-buy-btn');

        if (statusNode) {
            statusNode.className = `lightbox-status ${statusMeta.class || 'status-available'}`;
            statusNode.textContent = statusMeta.label || 'Disponible';
        }

        if (copyNode) {
            copyNode.textContent = this.currentArticleStatus === 'available'
                ? 'Disponible maintenant. Tu peux l ajouter au panier.'
                : `Statut actuel: ${statusMeta.label || 'Indisponible'}. La commande est desactivee tant que le produit n est pas redevenu disponible.`;
        }

        if (buttonNode) {
            const isAvailable = this.currentArticleStatus === 'available';
            buttonNode.classList.toggle('is-disabled', !isAvailable);
            buttonNode.textContent = isAvailable ? 'Acheter maintenant' : 'Indisponible pour le moment';
        }
    }

    updateFavoriteButton() {
        const favoriteBtn = document.getElementById('lightboxFavoriteBtn');
        if (!favoriteBtn) {
            return;
        }

        favoriteBtn.classList.toggle('is-active', this.currentArticleFavorite);
        favoriteBtn.setAttribute('aria-label', this.currentArticleFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris');
    }

    toggleFavorite() {
        if (!this.currentArticleId) {
            return;
        }

        fetch(`${this.baseUrl}wishlist_api.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ article_id: this.currentArticleId })
        })
            .then((response) => response.json())
            .then((data) => {
                if (!data.success) {
                    showNotification(data.message || 'Impossible de modifier les favoris.', 'warning', 'Favoris');
                    return;
                }

                this.currentArticleFavorite = !!data.is_favorite;
                this.updateFavoriteButton();

                const cardButton = document.querySelector(`.favorite-btn[data-article-id="${this.currentArticleId}"]`);
                if (cardButton) {
                    cardButton.classList.toggle('is-active', this.currentArticleFavorite);
                }

                showNotification(data.message || 'Liste d envies mise a jour.', 'success', 'Favoris');
            })
            .catch(() => {
                showNotification('Une erreur technique est survenue.', 'error', 'Favoris');
            });
    }
}

function updateCartCount() {
    const cart = JSON.parse(localStorage.getItem('efootball_cart') || '[]');
    const total = cart.length;
    const countEl = document.getElementById('cartCount');
    if (countEl) {
        countEl.textContent = total;
        countEl.style.display = total > 0 ? 'flex' : 'none';
    }
}

function showNotification(message, type = 'success', title = 'Information') {
    if (typeof window.notify === 'function') {
        window.notify(message, type, title);
        return;
    }

    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 30px;
        right: 30px;
        background: rgba(0, 255, 136, 0.2);
        border: 1px solid #00ff88;
        color: #00ff88;
        padding: 16px 24px;
        border-radius: 12px;
        font-family: 'Orbitron', sans-serif;
        font-size: 13px;
        letter-spacing: 1px;
        z-index: 1000;
        animation: notificationSlide 0.4s ease;
        backdrop-filter: blur(10px);
    `;

    notification.textContent = message;
    document.body.appendChild(notification);

    const style = document.createElement('style');
    style.textContent = `
        @keyframes notificationSlide {
            from {
                opacity: 0;
                transform: translateX(100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
    `;

    if (!document.querySelector('style[data-notification]')) {
        style.setAttribute('data-notification', '');
        document.head.appendChild(style);
    }

    setTimeout(() => {
        notification.style.animation = 'notificationSlide 0.4s ease reverse';
        setTimeout(() => notification.remove(), 400);
    }, 3000);
}

let articleLightbox;
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        articleLightbox = new ArticleLightbox();
        updateCartCount();
    });
} else {
    articleLightbox = new ArticleLightbox();
    updateCartCount();
}
