/**
 * Ashish Traders Fireworks (A&T) - Cart & WhatsApp Direct Order System
 * Builds dynamic quote estimates and pre-fills WhatsApp orders to Ashish Chopra & Yuvraj Chopra.
 * Completely silent (no audio synthesis) and optimized for real-time mobile/desktop shopping.
 */

class FireworksCart {
  constructor() {
    this.items = [];
    this.load();
    this.bindEvents();
    this.render();
  }

  load() {
    try {
      const saved = localStorage.getItem('at_fireworks_cart');
      if (saved) {
        this.items = JSON.parse(saved);
      }
    } catch (e) {
      this.items = [];
    }
  }

  save() {
    localStorage.setItem('at_fireworks_cart', JSON.stringify(this.items));
    this.updateBadges();
    // Dispatch custom event for product cards to sync their UI
    window.dispatchEvent(new CustomEvent('cartUpdated', { detail: { items: this.items } }));
  }

  getItemQuantity(productId) {
    const item = this.items.find(i => i.id === productId);
    return item ? item.quantity : 0;
  }

  addItem(productId, quantity = 1) {
    const product = window.FIREWORKS_PRODUCTS?.find(p => p.id === productId);
    if (!product) return;

    const existing = this.items.find(i => i.id === productId);
    if (existing) {
      existing.quantity += quantity;
    } else {
      this.items.push({
        id: product.id,
        name: product.name,
        brand: product.brand,
        price: product.price,
        packInfo: product.packInfo,
        image: product.image || 'assets/images/cock-skyshots-240.jpg',
        quantity: quantity
      });
    }

    this.save();
    this.render();
    this.openDrawer();
  }

  updateQuantity(productId, delta) {
    const item = this.items.find(i => i.id === productId);
    if (!item) return;

    item.quantity += delta;
    if (item.quantity <= 0) {
      this.items = this.items.filter(i => i.id !== productId);
    }
    this.save();
    this.render();
  }

  removeItem(productId) {
    this.items = this.items.filter(i => i.id !== productId);
    this.save();
    this.render();
  }

  clear() {
    this.items = [];
    this.save();
    this.render();
  }

  getTotal() {
    return this.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
  }

  getTotalItems() {
    return this.items.reduce((sum, item) => sum + item.quantity, 0);
  }

  updateBadges() {
    const count = this.getTotalItems();
    document.querySelectorAll('.cart-count-badge').forEach(badge => {
      badge.textContent = count;
      badge.style.display = count > 0 ? 'inline-flex' : 'none';
    });
  }

  openDrawer() {
    const drawer = document.getElementById('cartDrawer');
    const overlay = document.getElementById('cartOverlay');
    if (drawer && overlay) {
      drawer.classList.add('open');
      overlay.classList.add('open');
      document.body.style.overflow = 'hidden';
    }
  }

  closeDrawer() {
    const drawer = document.getElementById('cartDrawer');
    const overlay = document.getElementById('cartOverlay');
    if (drawer && overlay) {
      drawer.classList.remove('open');
      overlay.classList.remove('open');
      document.body.style.overflow = 'auto';
    }
  }

  generateWhatsAppUrl(rep = 'ashish') {
    const settings = window.getSiteSettings ? window.getSiteSettings() : {};
    const defaultPhone = rep === 'yuvraj' ? (settings.yuvrajPhone || '918630615934') : (settings.ashishPhone || '919837081321');
    const phone = defaultPhone.replace(/[^0-9]/g, '');
    const repName = rep === 'yuvraj' ? 'Yuvraj Chopra Ji' : 'Ashish Chopra Ji';

    const custNameEl = document.getElementById('cartCustomerName');
    const custName = (custNameEl && custNameEl.value.trim()) ? custNameEl.value.trim() : 'Valued Customer';

    const custCityEl = document.getElementById('cartCustomerCity');
    const custCity = (custCityEl && custCityEl.value.trim()) ? custCityEl.value.trim() : 'Dehradun';

    const showPrices = settings.showPrices === true;

    if (this.items.length === 0) {
      const defaultMsg = encodeURIComponent(`Hello ${repName}, I am contacting you from the Ashish Traders Fireworks website regarding crackers wholesale & retail inquiry for ${custCity}.`);
      return `https://wa.me/${phone}?text=${defaultMsg}`;
    }

    let text = `*FESTIVE FIREWORKS INQUIRY / ORDER - A&T*\n`;
    text += `*Ashish Traders Fireworks, Dehradun*\n`;
    text += `------------------------------------\n`;
    text += `*Customer:* ${custName}\n`;
    text += `*Location:* ${custCity}\n`;
    text += `*Showroom Hub:* 23, Mohabewala Ind. Area, Saharanpur Rd, Dehradun\n\n`;
    text += `*SELECTED PRODUCTS LIST:*\n`;

    this.items.forEach((item, index) => {
      if (showPrices) {
        const itemSubtotal = item.price * item.quantity;
        text += `${index + 1}. *[${item.brand}]* ${item.name}\n`;
        text += `   Qty: ${item.quantity} | Rate: ₹${item.price.toLocaleString('en-IN')} | Total: ₹${itemSubtotal.toLocaleString('en-IN')}\n`;
      } else {
        text += `${index + 1}. *[${item.brand}]* ${item.name} — *Qty: ${item.quantity}*\n`;
      }
    });

    const total = this.getTotal();
    const totalItems = this.getTotalItems();
    text += `------------------------------------\n`;
    text += `*Total Units:* ${totalItems} Packets / Boxes\n`;
    if (showPrices) {
      text += `*Grand Estimate Total: ₹${total.toLocaleString('en-IN')}*\n\n`;
    } else {
      text += `*Quotation Request:* Wholesale Factory Rates on WhatsApp\n\n`;
    }
    text += `Please confirm stock availability, wholesale discount slab, and warehouse pickup / delivery schedule. Thank you!`;

    return `https://wa.me/${phone}?text=${encodeURIComponent(text)}`;
  }

  render() {
    this.updateBadges();

    const container = document.getElementById('cartItemsList');
    const totalEl = document.getElementById('cartTotalAmount');
    const totalItemsEl = document.getElementById('cartTotalItemsCount');
    const emptyState = document.getElementById('cartEmptyState');
    const actionsEl = document.getElementById('cartActions');
    const settings = window.getSiteSettings ? window.getSiteSettings() : {};
    const showPrices = settings.showPrices === true;

    if (!container) return;

    if (this.items.length === 0) {
      container.innerHTML = '';
      if (emptyState) emptyState.style.display = 'flex';
      if (actionsEl) actionsEl.style.display = 'none';
      if (totalEl) totalEl.textContent = showPrices ? '₹0' : 'Inquiry List';
      if (totalItemsEl) totalItemsEl.textContent = '0 items';
      return;
    }

    if (emptyState) emptyState.style.display = 'none';
    if (actionsEl) actionsEl.style.display = 'block';

    container.innerHTML = this.items.map(item => `
      <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-900/80 border border-slate-800 hover:border-amber-500/40 transition-all">
        <div class="w-14 h-14 rounded-lg overflow-hidden bg-slate-950 border border-slate-700 flex-shrink-0">
          <img src="${item.image || 'assets/images/cock-skyshots-240.jpg'}" alt="${item.name}" class="w-full h-full object-cover">
        </div>
        <div class="flex-1 min-w-0">
          <h4 class="font-bold text-xs sm:text-sm text-slate-100 truncate">${item.name}</h4>
          <div class="flex items-center gap-2 mt-0.5">
            <span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-500/10 text-amber-400 font-semibold border border-amber-500/20">${item.brand}</span>
            ${showPrices ? `
              <span class="text-xs text-slate-300 font-medium">₹${item.price.toLocaleString('en-IN')}</span>
            ` : `
              <span class="text-[10px] text-emerald-400 font-semibold flex items-center gap-1">Wholesale Rate Inquiry</span>
            `}
          </div>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
          <div class="flex items-center bg-slate-800 rounded-lg border border-slate-700">
            <button onclick="window.cart.updateQuantity('${item.id}', -1)" class="w-7 h-7 flex items-center justify-center text-slate-300 hover:text-white hover:bg-slate-700 rounded-l-lg font-bold transition-colors">−</button>
            <span class="w-7 text-center text-xs font-bold text-amber-400">${item.quantity}</span>
            <button onclick="window.cart.updateQuantity('${item.id}', 1)" class="w-7 h-7 flex items-center justify-center text-slate-300 hover:text-white hover:bg-slate-700 rounded-r-lg font-bold transition-colors">+</button>
          </div>
          <button onclick="window.cart.removeItem('${item.id}')" class="text-slate-400 hover:text-red-400 p-1.5 transition-colors" title="Remove Item">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
          </button>
        </div>
      </div>
    `).join('');

    const total = this.getTotal();
    const totalCount = this.getTotalItems();
    if (totalEl) {
      totalEl.textContent = showPrices ? `₹${total.toLocaleString('en-IN')}` : 'Direct WhatsApp Quote';
      if (!showPrices) totalEl.className = 'text-base sm:text-lg font-black text-amber-400';
    }
    if (totalItemsEl) totalItemsEl.textContent = `${totalCount} item${totalCount !== 1 ? 's' : ''}`;

    this.refreshWhatsAppLinks();
  }

  refreshWhatsAppLinks() {
    const waAshishBtn = document.getElementById('cartWaAshish');
    const waYuvrajBtn = document.getElementById('cartWaYuvraj');
    if (waAshishBtn) waAshishBtn.href = this.generateWhatsAppUrl('ashish');
    if (waYuvrajBtn) waYuvrajBtn.href = this.generateWhatsAppUrl('yuvraj');
  }

  bindEvents() {
    document.querySelectorAll('.open-cart-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        this.openDrawer();
      });
    });

    document.querySelectorAll('#closeCartBtn, .close-cart-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        this.closeDrawer();
      });
    });
    const overlay = document.getElementById('cartOverlay');
    if (overlay) overlay.addEventListener('click', () => this.closeDrawer());

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') this.closeDrawer();
    });

    const nameInput = document.getElementById('cartCustomerName');
    const cityInput = document.getElementById('cartCustomerCity');
    if (nameInput) nameInput.addEventListener('input', () => this.refreshWhatsAppLinks());
    if (cityInput) cityInput.addEventListener('input', () => this.refreshWhatsAppLinks());
  }
}

// Global Cart Instance
window.cart = new FireworksCart();
