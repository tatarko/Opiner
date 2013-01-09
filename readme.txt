=============================
=========   Opiner   ========
= open source php framework =
=============================


Inštalácia
----------

1) Nakopírujte súbory z rozbaleného archívu do koreňového
adresára servru, na ktorom má bežať webová stránka

2) V prípade, že webová stránka potrebuje pre svoje fungovanie
aj MySQL databázu, otvorte súbor /private/config/default.php a vložte doň
správne hodnoty pre korektné pripojenie k MySQL servru a databáze.



Roadmap
-------

- Prepísanie triedy na prácu s databázou.
- Dokoncenie rozhrania modelov a ORM structurovania.
- Zjednodušenie šablonovacieho systému.
- Vytvorenie rozhrania na prácu s formulármi.
- (pridaj navrh na dalsie: tomas@tatarko.sk)



Zoznam zmien
------------

Opiner 0.4
Weekly Build #4 [09.01.2013]
- Pridany novy modul Cache (docasne uchovavanie rozlicnych hodnot).
- Pridany novy modul Menu (jednoduche spravovanie menu).
- Cely framework hadze teraz v pripade chyb Exception.
- Nove trieda na vykreslenie debug dat.
- Graficke osetrenie chyb spolu s debug informaciami.
- Optimalizacia triedy Image pre novu proformu frameworku.
- Kazdy controller je automaticky rozsireny o nove metody na pracu s menu.
- Pouzivatelske moduly sa nacitavaju teraz ako prve (pred systemovymi).
- Zakladny text pre default controller je teraz napojeny na aktualne zvoleny preklad systemu.
- Pri pridavani novych cyklickych premennych do templatu je mozne pouzit aj vnorene vkladanie.

Opiner 0.3
Weekly Build #3 [04.01.2013]
- Prekladovy system je uz plne funkcny.
- Parsery vstupnych hodnot su izolovane od template triedy.
- Doladenie Database modulu pre novu strukturu.
- Opiner\Application nacitava uz aj non-default moduly.
- Volanie modulov cez Opiner\Application::module($localname).
- Konfiguraciu aplikacie je mozne teraz uz aj nastavit cez
Opiner\Application::config($key, $value), vykona sa aj ulozenie do DB.
- Pri startupe modulu database sa odteraz nacitava konfiguracia z DB.
- Pridany .htaccess pre zakladne nastavenie modu rewrite.
- Modul Router a samotne routovanie aplikacie je uz funkcne.
- Pri pridavani novych premennych do templatu je mozne pouzit aj vnorene vkladanie.

Opiner 0.2
Weekly Build #2 [09.12.2012]
- Zmena súborovej štruktúry od základov.
- Rozdelenie frameworku na statickú časť a dynamickú (rozdielná pre každý
web bežiaci pod Opiner framworkom).
- Framework odteraz potrebuje pre svoj chod php verziu 5.4 a vyššiu.
- Odizolovanie názvoslovia tried, funkcií, ... pomocou php namespaces.
- Zriadené rozhranie na prekladanie stránok.
- Táto verzia nie je funkčným celkom, slúži len na ukážku novej filozofie
kódu. Odporúčame s nasadením a testovaním počkať na najbližšie verzie.

Opiner 0.1
Weekly Build #1 [02.12.2012]
- Prvá verzia v histórií verejne dostupná na internete.
- Ponúka šablonovací systém, Router, rozhranie na prácu s obrázkami,
databázou a ďalšie vychytávky.