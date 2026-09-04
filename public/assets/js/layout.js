/* Shared page chrome. Layout.public() injects the site header/nav/footer;
   Layout.admin(user) injects the admin bar. All markup here is static -
   the only dynamic value (the admin's email) is set via textContent. */
const Layout = {
    nav: [
        ["製品情報", "/products"],
        ["製品検索", "/products/search"],
        ["技術情報", "/technical"],
        ["会社情報", "/company"],
        ["お問い合わせ", "/contact"],
    ],

    public() {
        const path = location.pathname;
        const active = this.nav.map(([, href]) => href)
            .filter((href) => path === href || path.startsWith(href + "/"))
            .sort((a, b) => b.length - a.length)[0];

        const items = this.nav.map(([label, href]) =>
            `<li><a href="${href}"${href === active ? ' aria-current="page"' : ""}>${label}</a></li>`).join("");

        document.body.insertAdjacentHTML("afterbegin", `
<div class="topbar"><div class="wrap topbar__inner">
  <span class="topbar__tag">小型ギヤードモータ専門メーカー ｜ 歯車技術 × DCモータ技術</span>
  <span class="topbar__contact">技術・お見積りのお問い合わせ： <strong>03-XXXX-XXXX</strong></span>
</div></div>
<header class="site-header"><div class="wrap site-header__inner">
  <a class="brand" href="/"><span class="brand__mark">THINK&middot;ENGINEERING</span><span class="brand__sub">シンクエンジニアリング株式会社</span></a>
  <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav"><span></span><span></span><span></span><span class="visually-hidden">メニュー</span></button>
  <nav id="site-nav" class="site-nav" aria-label="グローバルナビゲーション"><ul>${items}</ul><a class="btn btn--primary site-nav__cta" href="/contact">お問い合わせ</a></nav>
</div></header>`);

        document.body.insertAdjacentHTML("beforeend", `
<footer class="site-footer"><div class="wrap site-footer__inner">
  <div><p class="site-footer__brand">THINK&middot;ENGINEERING</p><p class="site-footer__note">シンクエンジニアリング株式会社（デモサイト）</p></div>
  <nav aria-label="フッターナビゲーション"><ul><li><a href="/products/search">製品検索</a></li><li><a href="/technical">技術情報</a></li><li><a href="/company">会社情報</a></li><li><a href="/admin/login">管理画面</a></li></ul></nav>
</div><p class="site-footer__copy">&copy; ${new Date().getFullYear()} THINK ENGINEERING (demo). All rights reserved.</p></footer>`);

        // Mobile nav toggle (jQuery, per the allowed stack).
        $(".nav-toggle").on("click", function () {
            const open = $("#site-nav").toggleClass("is-open").hasClass("is-open");
            $(this).attr("aria-expanded", String(open));
        });
    },

    /* The admin bar is static markup in each admin page (so it paints before any
       API call); this just fills in the user and wires logout. */
    admin(user) {
        const who = document.querySelector(".admin-bar__user");
        if (who) who.textContent = user.email;
        document.querySelector(".admin-bar__logout")?.addEventListener("click", async () => {
            await api.post("/api/admin/logout");
            location.href = "/admin/login";
        });
    },
};
