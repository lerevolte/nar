{{-- Общие шапка + мобильное меню (дизайн 2026). Стили: inline на главной (landing2), на остальных страницах — /css/chrome2.css --}}
<!-- ======================= MOBILE DRAWER ======================= -->
<div class="mob-overlay" id="mobOverlay" aria-hidden="true"></div>
<nav class="mob-drawer" id="mobDrawer" aria-hidden="true">
  <button class="mob-close" id="mobClose" aria-label="Закрыть меню">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"></path></svg>
  </button>
  <ul class="mob-nav">
    <li><a href="/">Главная</a></li>
    <li><a href="/articles">Статьи</a></li>
    <li><a href="/pages/povod-dlya-pesni/den-rozhdeniya">Повод для песни</a></li>
    <li><a href="/help">Помощь</a></li>
    <li><a href="/tarify">Тарифы</a></li>
    <li><a href="/create-song">Создать трек</a></li>
    @if(isset($authUser) && $authUser)
    <li><a href="{{ route('dashboard') }}">Личный кабинет</a></li>
    @else
    <li><a href="/login">Вход</a></li>
    <li><a href="/register">Регистрация</a></li>
    @endif
  </ul>
</nav>

<!-- ======================= HEADER ======================= -->
<header class="hdr">
  <div class="wrap hdr-in">
    <button class="mob-burger" id="mobBurger" aria-label="Открыть меню">
      <span></span><span></span><span></span>
    </button>
    <a href="/" class="logo">
      <img src="/img/logo1.svg" alt="На Репите" class="logo-img">
    </a>
    <nav class="nav">
      <a href="/">Главная</a>
      <a href="/articles">Статьи</a>
      <a href="/pages/povod-dlya-pesni/den-rozhdeniya">Повод для песни</a>
      <a href="/tarify">Тарифы</a>
      @if(request()->routeIs('home'))
      <a href="#faq">FAQ</a>
      @else
      <a href="/create-song">Создать трек</a>
      @endif
      <a href="/help">Помощь</a>
    </nav>
    <div class="hdr-cta">
      @if(isset($authUser) && $authUser)
        <a href="{{ route('dashboard') }}" class="btn btn--primary btn--sm">Личный кабинет</a>
      @else
        <a href="/login" class="link-login">Вход</a>
        <a href="/register" class="btn btn--primary btn--sm">Регистрация</a>
      @endif
    </div>
  </div>
</header>

<script>
// Mobile drawer
(function(){
  var burger=document.getElementById('mobBurger');
  var drawer=document.getElementById('mobDrawer');
  var overlay=document.getElementById('mobOverlay');
  var close=document.getElementById('mobClose');
  if(!burger||!drawer||!overlay)return;
  function open(){drawer.classList.add('is-open');overlay.classList.add('is-open');document.body.style.overflow='hidden';drawer.setAttribute('aria-hidden','false');}
  function shut(){drawer.classList.remove('is-open');overlay.classList.remove('is-open');document.body.style.overflow='';drawer.setAttribute('aria-hidden','true');}
  burger.addEventListener('click',open);
  if(close)close.addEventListener('click',shut);
  overlay.addEventListener('click',shut);
  document.addEventListener('keydown',function(e){if(e.key==='Escape')shut();});
  drawer.querySelectorAll('a').forEach(function(a){a.addEventListener('click',shut);});
})();
</script>
