const menuBtn = document.querySelector('.menu-btn');
const hamburger = document.querySelector('.menu-btn__burger');
const nav = document.querySelector('.nav');
const menuNav = document.querySelector('.menu-nav');
const navItems = document.querySelectorAll('.menu-nav__item');

let showMenu = false;

menuBtn.addEventListener('click', toggleMenu);

function toggleMenu() {
  if(!showMenu) {
    hamburger.classList.add('open');
    nav.classList.add('open');
    menuNav.classList.add('open');
    navItems.forEach(item => item.classList.add('open'));

    showMenu = true;
  } else {
    hamburger.classList.remove('open');
    nav.classList.remove('open');
    menuNav.classList.remove('open');
    navItems.forEach(item => item.classList.remove('open'));

    showMenu = false;
  }
}

const device = document.getElementById('Device');
const type = document.getElementById('Type');
const locat = document.getElementById('Location');
const day = document.getElementById('Day');
const week = document.getElementById('Week');
const month = document.getElementById('Month');
const year = document.getElementById('Year');

const timeItems = document.getElementsByClassName('displayTime__link');
const typeItems = document.getElementsByClassName('displayType__link');

const displayTime = document.getElementById('passDisplayTime').innerText;
const displayType = document.getElementById('passDisplayType').innerText;

if(displayTime !== null) {
  switch(displayTime) {
    case 'Day':
      toggleGraph(day, timeItems);
      break;
    case 'Week':
      toggleGraph(week, timeItems);
      break;
    case 'Month':
        toggleGraph(month, timeItems);
        break;
    case 'Year':
      toggleGraph(year, timeItems);
      break;
  }
}

if(displayType !== null) {
  switch(displayType) {
    case 'Device':
      toggleGraph(device, typeItems);
      break;
    case 'Type':
      toggleGraph(type, typeItems);
      break;
    case 'Location':
        toggleGraph(locat, typeItems);
        break;
  }
}

function toggleGraph(id, list) {
  for(var i = 0; i < list.length; i++) {
    var item = list[i];
    item.classList.remove('active');
  }
  id.classList.add('active');
}