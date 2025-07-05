"use strict";
var savedConfig = localStorage.getItem("__CONFIG__"),
  defaultConfig = { theme: "light" },
  config = Object.assign(defaultConfig, JSON.parse(savedConfig)),
  saveState = function () {
    localStorage.setItem("__CONFIG__", JSON.stringify(config));
  },
  changeThemeMode = function (e) {
    document.getElementsByTagName("html")[0].setAttribute("data-bs-theme", e),
      (config.theme = e),
      saveState();
  },
  init = function () {
    window.addEventListener("load", initTheme);
  },
  initTheme = function () {
    var e = document.getElementById("light-dark-mode");
    e &&
      e.addEventListener("click", function (e) {
        "light" === config.theme
          ? changeThemeMode("dark")
          : changeThemeMode("light");
      });
  };
changeThemeMode(config.theme), init();
