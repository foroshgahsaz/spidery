document.addEventListener('DOMContentLoaded', () => {
  initHeaderMetrics();
  initMobileBottomNavMetrics();
  initMegaMenu();
  initMainSwiper();
  initProductSwipers();
  initBlogSwiper();
  initProgTabs();
  initCartEvents();
  initHeaderUserMenu();
  initShopAdminBarOffset();
  initSearchModalEvents();
});

function initHeaderMetrics() {
  const header = document.querySelector('.shop-header');
  if (!header) return;

  const sync = () => {
    document.documentElement.style.setProperty('--shop-header-height', `${header.offsetHeight}px`);
  };

  sync();

  if (typeof ResizeObserver !== 'undefined') {
    const observer = new ResizeObserver(sync);
    observer.observe(header);
  }

  window.addEventListener('resize', sync);
}

function initMobileBottomNavMetrics() {
  const nav = document.querySelector('.mobile-bottom-nav');
  if (!nav) return;

  const sync = () => {
    const height = nav.offsetHeight;
    document.documentElement.style.setProperty('--mobile-bottom-nav-height', `${height}px`);
  };

  sync();

  if (typeof ResizeObserver !== 'undefined') {
    const observer = new ResizeObserver(sync);
    observer.observe(nav);
  }

  window.addEventListener('resize', sync);
}

function initSearchModalEvents() {
  const modal = document.getElementById('searchModal');
  if (!modal) return;

  modal.addEventListener('click', (event) => {
    if (event.target === modal) {
      toggleSearchModal(false);
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && modal.classList.contains('is-open')) {
      toggleSearchModal(false);
    }
  });
}

function toggleSearchModal(show) {
  const modal = document.getElementById('searchModal');
  if (!modal) return;

  const input = modal.querySelector('.search-modal__input');

  if (show) {
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    setTimeout(() => input?.focus(), 200);
  } else {
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }
}

window.toggleSearchModal = toggleSearchModal;

function initHeaderUserMenu() {
  const closeAll = () => {
    document.querySelectorAll('.header-user-dropdown.open').forEach((dropdown) => {
      dropdown.classList.remove('open');
      dropdown.querySelector('.header-user-dropdown-toggle')?.setAttribute('aria-expanded', 'false');
    });
  };

  document.addEventListener('click', (event) => {
    const toggle = event.target.closest('.header-user-dropdown-toggle');
    if (toggle) {
      event.stopPropagation();
      const dropdown = toggle.closest('.header-user-dropdown');
      if (!dropdown) return;

      const willOpen = !dropdown.classList.contains('open');
      closeAll();
      if (willOpen) {
        dropdown.classList.add('open');
        toggle.setAttribute('aria-expanded', 'true');
      }
      return;
    }

    if (!event.target.closest('.header-user-dropdown')) {
      closeAll();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeAll();
  });
}

function initShopAdminBarOffset() {
  const bar = document.getElementById('storeAdminBar');
  if (!bar) return;

  const header = document.querySelector('.shop-header');

  const sync = () => {
    const barHeight = bar.offsetHeight;
    document.documentElement.style.setProperty('--shop-admin-bar-height', `${barHeight}px`);

    if (header) {
      document.documentElement.style.setProperty('--shop-header-height', `${header.offsetHeight}px`);
    }
  };

  sync();

  if (typeof ResizeObserver !== 'undefined') {
    const observer = new ResizeObserver(sync);
    observer.observe(bar);
    if (header) observer.observe(header);
  }

  window.addEventListener('resize', sync);
}

function initCartEvents() {
  document.addEventListener('click', (event) => {
    if (event.target.closest('[data-open-cart]')) {
      event.preventDefault();
      toggleCartSidebar(true);
      return;
    }

    if (event.target.closest('[data-close-cart]')) {
      event.preventDefault();
      toggleCartSidebar(false);
    }
  });

  const registerLivewireCartEvents = () => {
    if (typeof Livewire === 'undefined') return;
    Livewire.on('open-cart-sidebar', () => toggleCartSidebar(true));
  };

  document.addEventListener('livewire:init', registerLivewireCartEvents);
  registerLivewireCartEvents();

  const sidebar = document.getElementById('cartSidebar');
  sidebar?.querySelector('[data-cart-backdrop]')?.addEventListener('click', () => toggleCartSidebar(false));
}

function toggleCartSidebar(show) {
  const sidebar = document.getElementById('cartSidebar');
  const content = document.getElementById('cartContent');
  const backdrop = sidebar?.querySelector('[data-cart-backdrop]');
  if (!sidebar || !content || !backdrop) return;

  if (show) {
    window.Livewire?.dispatch('load-cart-sidebar');
    sidebar.classList.remove('invisible');
    sidebar.classList.add('is-open');
    document.body.classList.add('cart-sidebar-open');
    requestAnimationFrame(() => {
      content.classList.add('open');
    });
  } else {
    content.classList.remove('open');
    sidebar.classList.remove('is-open');
    document.body.classList.remove('cart-sidebar-open');
    setTimeout(() => sidebar.classList.add('invisible'), 300);
  }
}

window.toggleCartSidebar = toggleCartSidebar;

function initMainSwiper() {
  const root = document.querySelector('.mainSwiper');
  if (!root || typeof Swiper === 'undefined') return null;

  const swiper = new Swiper(root, {
    slidesPerView: 'auto',
    centeredSlides: true,
    spaceBetween: 10,
    grabCursor: true,
    loop: true,
    loopAdditionalSlides: 3,
    loopedSlides: 3,
    watchSlidesProgress: true,
    pagination: {
      el: root.querySelector('.swiper-pagination'),
      clickable: true,
    },
    breakpoints: {
      768: { spaceBetween: 18 },
      1200: { spaceBetween: 20 },
    },
    on: {
      init(instance) {
        requestAnimationFrame(() => {
          instance.slideToLoop(0, 0, false);
          instance.update();
        });
      },
    },
  });

  return swiper;
}

function createAutoHorizontalSwiper(el, { spaceBetween = 16, withNav = true, navRoot = null } = {}) {
  if (!el || typeof Swiper === 'undefined') {
    return null;
  }

  const section = el.closest('section');
  const navScope = navRoot || section;
  const config = {
    slidesPerView: 'auto',
    spaceBetween,
    grabCursor: true,
    threshold: 12,
    touchAngle: 30,
    touchReleaseOnEdges: true,
    slideToClickedSlide: false,
    preventClicks: true,
    preventClicksPropagation: true,
    resistanceRatio: 0.85,
    freeMode: {
      enabled: true,
      momentum: true,
      momentumRatio: 0.9,
      momentumVelocityRatio: 0.9,
      sticky: false,
    },
  };

  if (withNav) {
    config.navigation = {
      nextEl: navScope?.querySelector('[data-swiper-next]'),
      prevEl: navScope?.querySelector('[data-swiper-prev]'),
    };
  }

  return new Swiper(el, config);
}

function initFeaturedDealsSwiper(el) {
  const slider = el.closest('.featured-deals-slider');
  el.querySelectorAll('.deal-card__cart-btn, .featured-deals-nav-btn').forEach((node) => {
    node.classList.add('swiper-no-swiping');
  });

  return createAutoHorizontalSwiper(el, {
    spaceBetween: 10,
    withNav: true,
    navRoot: slider,
  });
}

function initProductSwipers() {
  document.querySelectorAll('.categorySwiper').forEach((el) => {
    createAutoHorizontalSwiper(el, { spaceBetween: 25 });
  });

  document.querySelectorAll('.featuredDealsSwiper').forEach((el) => {
    initFeaturedDealsSwiper(el);
  });

  document.querySelectorAll('.programmingSwiper').forEach((el) => {
    createAutoHorizontalSwiper(el);
  });

  document.querySelectorAll('.productSwiper').forEach((el) => {
    createAutoHorizontalSwiper(el, { spaceBetween: 12 });
  });
}

function initBlogSwiper() {
  const root = document.querySelector('.blogSwiper');
  if (!root || typeof Swiper === 'undefined') {
    return;
  }

  new Swiper(root, {
    slidesPerView: 1.1,
    spaceBetween: 14,
    grabCursor: true,
    navigation: {
      nextEl: '.blog-nav-next',
      prevEl: '.blog-nav-prev',
    },
    breakpoints: {
      640: { slidesPerView: 2, spaceBetween: 16 },
      1024: { slidesPerView: 3, spaceBetween: 18 },
      1280: { slidesPerView: 4, spaceBetween: 20 },
    },
  });
}

function initMegaMenu() {
  const container = document.getElementById('megaMenuContainer');
  const megaBox = document.getElementById('megaMenuBox');
  const megaBackdrop = document.getElementById('megaMenuBackdrop');
  if (!container || !megaBox || container.dataset.megaBound === '1') {
    return;
  }

  container.dataset.megaBound = '1';

  let timer = null;
  const open = () => {
    if (timer) { clearTimeout(timer); timer = null; }
    megaBox.classList.add('is-open');
    megaBox.setAttribute('aria-hidden', 'false');
    document.body.classList.add('mega-menu-open');
  };
  const close = () => {
    timer = setTimeout(() => {
      megaBox.classList.remove('is-open');
      megaBox.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('mega-menu-open');
    }, 180);
  };

  container.addEventListener('mouseenter', open);
  container.addEventListener('mouseleave', close);
  megaBox.addEventListener('mouseenter', open);
  megaBox.addEventListener('mouseleave', close);
  megaBackdrop?.addEventListener('click', close);
}

function initProgTabs() {
  const tabs = document.querySelectorAll('[data-prog-tab]');
  tabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      tabs.forEach((t) => t.classList.remove('active'));
      tab.classList.add('active');
    });
  });
}


function toggleElement(id, show) {
  const el = document.getElementById(id);
  if (!el) return;

  if (id === 'loginModal') {
    const inner = el.querySelector('[data-modal-inner]');
    if (show) {
      el.classList.remove('invisible', 'pointer-events-none');
      el.classList.add('bg-black/60', 'backdrop-blur-sm');
      inner.classList.replace('scale-95', 'scale-100');
      inner.classList.replace('opacity-0', 'opacity-100');
    } else {
      el.classList.remove('bg-black/60', 'backdrop-blur-sm');
      inner.classList.replace('scale-100', 'scale-95');
      inner.classList.replace('opacity-100', 'opacity-0');
      setTimeout(() => el.classList.add('invisible', 'pointer-events-none'), 300);
    }
    return;
  }

  if (id === 'mobileMenu') {
    if (show) {
      el.classList.add('is-open');
      document.body.classList.add('mobile-menu-open');
      document.body.style.overflow = 'hidden';
    } else {
      el.classList.remove('is-open');
      document.body.classList.remove('mobile-menu-open');
      document.body.style.overflow = '';
    }
    return;
  }

  el.classList.toggle('hidden', !show);
  el.classList.toggle('flex', show);
}

function toggleAccordion(id) {
  const menu = document.getElementById(id);
  const icon = document.getElementById('accordionIcon');
  const open = menu.classList.contains('hidden');
  menu.classList.toggle('hidden', !open);
  menu.classList.toggle('flex', open);
  icon.classList.toggle('rotate-180', open);
}

function switchTab(type) {
  const otpBtn = document.getElementById('tabOtpBtn');
  const userBtn = document.getElementById('tabUserBtn');
  const formOtp = document.getElementById('formOtp');
  const formUser = document.getElementById('formUser');
  const active = 'flex-1 py-4 border-b-2 border-brand-green text-brand-green font-bold';
  const idle = 'flex-1 py-4 border-b-2 border-transparent text-gray-500 hover:text-gray-700';

  if (type === 'otp') {
    otpBtn.className = active;
    userBtn.className = idle;
    formOtp.classList.replace('hidden', 'flex');
    formUser.classList.add('hidden');
    formUser.classList.remove('flex');
  } else {
    userBtn.className = active;
    otpBtn.className = idle;
    formUser.classList.replace('hidden', 'flex');
    formOtp.classList.add('hidden');
    formOtp.classList.remove('flex');
  }
}

window.toggleElement = toggleElement;
window.toggleAccordion = toggleAccordion;
window.switchTab = switchTab;
