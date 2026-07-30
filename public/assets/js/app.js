(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    initBurgerMenu();
    initHeroSlider();
    initAddToCart();
    initQtyControls();
    initCartUpdateForms();
    initRemoveForms();
  });

  /* ---------------------------------------------------------------------
   * Бургер-меню: доступное открытие/закрытие
   * ------------------------------------------------------------------- */
  function initBurgerMenu() {
    var toggle = document.getElementById('burgerToggle');
    var nav = document.getElementById('mainNav');
    if (!toggle || !nav) {
      return;
    }

    function close() {
      nav.classList.remove('is-open');
      toggle.setAttribute('aria-expanded', 'false');
    }

    function open() {
      nav.classList.add('is-open');
      toggle.setAttribute('aria-expanded', 'true');
    }

    toggle.addEventListener('click', function () {
      var expanded = toggle.getAttribute('aria-expanded') === 'true';
      if (expanded) {
        close();
      } else {
        open();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
        close();
        toggle.focus();
      }
    });

    document.addEventListener('click', function (event) {
      var target = event.target;
      if (toggle.getAttribute('aria-expanded') !== 'true') {
        return;
      }
      if (!nav.contains(target) && target !== toggle && !toggle.contains(target)) {
        close();
      }
    });

    // На широких экранах меню всегда видно — сбрасываем состояние при ресайзе.
    window.addEventListener('resize', function () {
      if (window.innerWidth >= 1024) {
        close();
      }
    });
  }

  /* ---------------------------------------------------------------------
   * Слайдер на главной: автопрокрутка, стрелки, точки, свайп, пауза на hover
   * ------------------------------------------------------------------- */
  function initHeroSlider() {
    var root = document.getElementById('heroSlider');
    var track = document.getElementById('heroSliderTrack');
    if (!root || !track) {
      return;
    }

    var slides = track.children;
    var dots = document.querySelectorAll('#heroDots .hero-slider__dot');
    var prevBtn = document.getElementById('heroPrev');
    var nextBtn = document.getElementById('heroNext');
    var count = slides.length;
    var index = 0;
    var timer = null;
    var AUTOPLAY_MS = 6000;

    function goTo(i) {
      index = (i + count) % count;
      track.style.transform = 'translateX(-' + (index * 100) + '%)';
      for (var d = 0; d < dots.length; d++) {
        dots[d].classList.toggle('is-active', d === index);
      }
    }

    function next() {
      goTo(index + 1);
    }

    function prev() {
      goTo(index - 1);
    }

    function start() {
      stop();
      if (count > 1) {
        timer = window.setInterval(next, AUTOPLAY_MS);
      }
    }

    function stop() {
      if (timer !== null) {
        window.clearInterval(timer);
        timer = null;
      }
    }

    if (nextBtn) {
      nextBtn.addEventListener('click', function () {
        next();
        start();
      });
    }
    if (prevBtn) {
      prevBtn.addEventListener('click', function () {
        prev();
        start();
      });
    }

    for (var i = 0; i < dots.length; i++) {
      dots[i].addEventListener('click', function (event) {
        var target = event.currentTarget;
        goTo(parseInt(target.getAttribute('data-slide'), 10) || 0);
        start();
      });
    }

    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', start);
    root.addEventListener('focusin', stop);
    root.addEventListener('focusout', start);

    // Свайп на тач-устройствах.
    var touchStartX = null;
    root.addEventListener('touchstart', function (event) {
      touchStartX = event.touches[0].clientX;
      stop();
    }, { passive: true });

    root.addEventListener('touchend', function (event) {
      if (touchStartX === null) {
        return;
      }
      var deltaX = event.changedTouches[0].clientX - touchStartX;
      if (Math.abs(deltaX) > 40) {
        if (deltaX < 0) {
          next();
        } else {
          prev();
        }
      }
      touchStartX = null;
      start();
    });

    goTo(0);
    start();
  }

  /* ---------------------------------------------------------------------
   * AJAX «В корзину» + счётчик + всплывающее уведомление
   * ------------------------------------------------------------------- */
  function initAddToCart() {
    var forms = document.querySelectorAll('.js-add-to-cart-form');
    forms.forEach(function (form) {
      form.addEventListener('submit', function (event) {
        if (!window.fetch) {
          return; // без fetch форма отправится обычным способом
        }
        event.preventDefault();

        var submitBtn = form.querySelector('.js-add-to-cart, button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
        }

        postForm(form)
          .then(function (data) {
            if (data && data.ok) {
              updateCartCount(data.count);
              showToast(data.message || 'Товар добавлен в корзину.');
            } else {
              showToast('Не получилось добавить товар.', true);
            }
          })
          .catch(function () {
            // AJAX не сработал — отправляем форму обычным способом (рабочий fallback).
            form.submit();
          })
          .finally(function () {
            if (submitBtn) {
                submitBtn.disabled = false;
            }
          });
      });
    });
  }

  /* ---------------------------------------------------------------------
   * Кнопки +/- количества (страница товара и корзина)
   * ------------------------------------------------------------------- */
  function initQtyControls() {
    document.querySelectorAll('.qty-control').forEach(function (control) {
      var input = control.querySelector('input[type="number"]');
      var minus = control.querySelector('.js-qty-minus');
      var plus = control.querySelector('.js-qty-plus');
      if (!input) {
        return;
      }

      function clamp(value) {
        var min = parseInt(input.getAttribute('min'), 10) || 1;
        var max = parseInt(input.getAttribute('max'), 10) || 9999;
        return Math.min(max, Math.max(min, value));
      }

      if (minus) {
        minus.addEventListener('click', function () {
          input.value = clamp((parseInt(input.value, 10) || 1) - 1);
          input.dispatchEvent(new Event('change', { bubbles: true }));
        });
      }
      if (plus) {
        plus.addEventListener('click', function () {
          input.value = clamp((parseInt(input.value, 10) || 1) + 1);
          input.dispatchEvent(new Event('change', { bubbles: true }));
        });
      }
      input.addEventListener('change', function () {
        input.value = clamp(parseInt(input.value, 10) || 1);
      });
    });
  }

  /* ---------------------------------------------------------------------
   * Корзина: изменение количества без перезагрузки страницы
   * ------------------------------------------------------------------- */
  function initCartUpdateForms() {
    var forms = document.querySelectorAll('.js-update-form');
    if (!forms.length || !window.fetch) {
      return;
    }

    forms.forEach(function (form) {
      var input = form.querySelector('.js-qty-input');
      if (!input) {
        return;
      }

      var submitTimer = null;
      input.addEventListener('change', function () {
        window.clearTimeout(submitTimer);
        submitTimer = window.setTimeout(function () {
          submitCartUpdate(form);
        }, 200);
      });
    });
  }

  function submitCartUpdate(form) {
    var row = form.closest('tr');
    var productId = form.querySelector('input[name="product_id"]').value;

    postForm(form)
      .then(function (data) {
        if (!data || !data.ok) {
          window.location.reload();
          return;
        }
        updateCartCount(data.count);
        updateSummary(data.totals);

        var item = (data.items || []).find(function (it) {
          return String(it.product.id) === String(productId);
        });
        if (row) {
          var lineCell = row.querySelector('.js-line-total');
          if (item && lineCell) {
            lineCell.textContent = formatPrice(item.line_total);
          } else if (!item) {
            row.remove();
          }
        }
        if ((data.items || []).length === 0) {
          window.location.reload();
        }
      })
      .catch(function () {
        form.submit();
      });
  }

  /* ---------------------------------------------------------------------
   * Корзина: удаление позиции с подтверждением
   * ------------------------------------------------------------------- */
  function initRemoveForms() {
    var forms = document.querySelectorAll('.js-remove-form');
    forms.forEach(function (form) {
      form.addEventListener('submit', function (event) {
        var confirmed = window.confirm('Удалить товар из корзины?');
        if (!confirmed) {
          event.preventDefault();
          return;
        }

        if (!window.fetch) {
          return; // обычная отправка формы
        }
        event.preventDefault();

        var row = form.closest('tr');
        postForm(form)
          .then(function (data) {
            if (!data || !data.ok) {
              window.location.reload();
              return;
            }
            updateCartCount(data.count);
            updateSummary(data.totals);
            if (row) {
              row.remove();
            }
            var remaining = document.querySelectorAll('#cartTable tbody tr').length;
            if (remaining === 0) {
              window.location.reload();
            }
          })
          .catch(function () {
            form.submit();
          });
      });
    });
  }

  /* ---------------------------------------------------------------------
   * Вспомогательные функции
   * ------------------------------------------------------------------- */
  function postForm(form) {
    var formData = new FormData(form);
    return window.fetch(form.getAttribute('action'), {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: formData,
      credentials: 'same-origin'
    }).then(function (response) {
      if (!response.ok) {
        throw new Error('Request failed');
      }
      return response.json();
    });
  }

  function updateCartCount(count) {
    var badge = document.getElementById('cartCount');
    var nav = document.querySelector('.main-nav__link[href$="/cart"]');
    if (count > 0) {
      if (!badge && nav) {
        badge = document.createElement('span');
        badge.className = 'main-nav__count';
        badge.id = 'cartCount';
        nav.appendChild(badge);
      }
      if (badge) {
        badge.textContent = String(count);
      }
    } else if (badge) {
      badge.remove();
    }
  }

  function updateSummary(totals) {
    if (!totals) {
      return;
    }
    var subtotal = document.querySelector('.js-summary-subtotal');
    var discount = document.querySelector('.js-summary-discount');
    var total = document.querySelector('.js-summary-total');
    if (subtotal) {
        subtotal.textContent = formatPrice(totals.subtotal);
    }
    if (discount) {
        discount.textContent = '−' + formatPrice(totals.discount);
    }
    if (total) {
        total.textContent = formatPrice(totals.total);
    }
  }

  function formatPrice(value) {
    var num = Number(value) || 0;
    var hasFraction = Math.abs(num - Math.round(num)) > 0.0001;
    var formatted = num.toLocaleString('ru-RU', {
      minimumFractionDigits: hasFraction ? 2 : 0,
      maximumFractionDigits: hasFraction ? 2 : 0
    });
    return formatted + ' ₽';
  }

  var toastTimer = null;
  function showToast(message, isError) {
    var toast = document.getElementById('appToast');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'appToast';
      toast.className = 'toast';
      toast.setAttribute('role', 'status');
      toast.setAttribute('aria-live', 'polite');
      document.body.appendChild(toast);
    }
    toast.textContent = message;
    toast.style.background = isError ? 'var(--danger)' : 'var(--ink)';
    toast.classList.add('is-visible');

    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(function () {
      toast.classList.remove('is-visible');
    }, 2600);
  }
})();
