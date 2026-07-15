{{-- Общий футер (дизайн 2026). Стили: inline на главной (landing2), на остальных страницах — /css/chrome2.css --}}
<footer class="ftr">
  <div class="wrap">
    <div class="ftr-grid">
      <div>
        <a href="/" class="logo">
          <img src="/img/logo1.svg" alt="На Репите" class="logo-img">
        </a>
        <p class="ftr-about">Нейросеть для создания песен с живым русским вокалом — без акцента, без VPN и без регистрации.</p>
      </div>
      <div>
        <h4>Навигация</h4>
        <ul>
          <li><a href="/articles">Статьи</a></li>
          <li><a href="/pages/povod-dlya-pesni">Повод для песни</a></li>
          <li><a href="/tarify">Тарифы</a></li>
          @if(isset($authUser) && $authUser)
          <li><a href="{{ route('dashboard') }}">Личный кабинет</a></li>
          @else
          <li><a href="/login">Вход</a></li>
          <li><a href="/register">Регистрация</a></li>
          @endif
          <li><a href="/oferta">Оферта</a></li>
          <li><a href="/privacy">Политика конфиденциальности</a></li>
        </ul>
      </div>
      <div>
        <h4>Мессенджеры</h4>
        <ul>
          <li><a href="https://t.me/na_repitebot">Telegram-бот</a></li>
          <li><a href="https://max.ru/id501216944367_bot">MAX-бот</a></li>
        </ul>
      </div>
    </div>
    <div class="ftr-bot">
      <span>© {{ date('Y') }} На Репите. Все права защищены.</span>
      <span>Создайте свой трек — и он окажется у всех «на репите».</span>
    </div>
  </div>
</footer>
