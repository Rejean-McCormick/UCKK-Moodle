// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Time-aware login background selector for theme_uckk.
 *
 * The module uses an institutional solar position supplied by PHP. It does not
 * use browser geolocation, IP geolocation, cookies, tracking, or external APIs.
 *
 * Periods:
 * - night;
 * - between: twilightMinutes before sunrise;
 * - day: sunrise to sunset;
 * - between: twilightMinutes after sunset;
 * - night.
 *
 * @module     theme_uckk/login_background
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const BODY_CLASSES = [
    'theme-uckk-login-background--day',
    'theme-uckk-login-background--between',
    'theme-uckk-login-background--night',
];

const radians = degrees => degrees * Math.PI / 180;

const degrees = value => value * 180 / Math.PI;

const normalise = (value, cycle) => {
    let result = value % cycle;

    if (result < 0) {
        result += cycle;
    }

    return result;
};

const localDayNumber = date => {
    const start = new Date(date.getFullYear(), 0, 0);
    const diff = date - start + ((start.getTimezoneOffset() - date.getTimezoneOffset()) * 60000);

    return Math.floor(diff / 86400000);
};

const localYmd = date => {
    return (date.getFullYear() * 10000) + ((date.getMonth() + 1) * 100) + date.getDate();
};

const alignToLocalDate = (candidate, target) => {
    const targetYmd = localYmd(target);
    let result = candidate;
    let guard = 0;

    while (localYmd(result) < targetYmd && guard < 3) {
        result = new Date(result.getTime() + 86400000);
        guard++;
    }

    while (localYmd(result) > targetYmd && guard < 6) {
        result = new Date(result.getTime() - 86400000);
        guard++;
    }

    return result;
};

const solarEvent = (date, latitude, longitude, sunrise) => {
    if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
        return null;
    }

    const day = localDayNumber(date);
    const longitudeHour = longitude / 15;
    const approximateTime = sunrise
        ? day + ((6 - longitudeHour) / 24)
        : day + ((18 - longitudeHour) / 24);

    const meanAnomaly = (0.9856 * approximateTime) - 3.289;
    let trueLongitude = meanAnomaly
        + (1.916 * Math.sin(radians(meanAnomaly)))
        + (0.020 * Math.sin(radians(2 * meanAnomaly)))
        + 282.634;
    trueLongitude = normalise(trueLongitude, 360);

    let rightAscension = degrees(Math.atan(0.91764 * Math.tan(radians(trueLongitude))));
    rightAscension = normalise(rightAscension, 360);

    const longitudeQuadrant = Math.floor(trueLongitude / 90) * 90;
    const rightAscensionQuadrant = Math.floor(rightAscension / 90) * 90;
    rightAscension = rightAscension + (longitudeQuadrant - rightAscensionQuadrant);
    rightAscension = rightAscension / 15;

    const sinDeclination = 0.39782 * Math.sin(radians(trueLongitude));
    const cosDeclination = Math.cos(Math.asin(sinDeclination));

    const cosHour = (
        Math.cos(radians(90.833)) - (sinDeclination * Math.sin(radians(latitude)))
    ) / (cosDeclination * Math.cos(radians(latitude)));

    if (cosHour > 1 || cosHour < -1) {
        return null;
    }

    let localHourAngle = sunrise
        ? 360 - degrees(Math.acos(cosHour))
        : degrees(Math.acos(cosHour));
    localHourAngle = localHourAngle / 15;

    const localMeanTime = localHourAngle + rightAscension - (0.06571 * approximateTime) - 6.622;
    const universalTime = normalise(localMeanTime - longitudeHour, 24);

    const hours = Math.floor(universalTime);
    const minutesFloat = (universalTime - hours) * 60;
    const minutes = Math.floor(minutesFloat);
    const seconds = Math.floor((minutesFloat - minutes) * 60);

    const candidate = new Date(Date.UTC(
        date.getFullYear(),
        date.getMonth(),
        date.getDate(),
        hours,
        minutes,
        seconds,
        0
    ));

    return alignToLocalDate(candidate, date);
};

const parseLocalTime = (date, value) => {
    const parts = String(value || '').split(':');
    const hours = Number.parseInt(parts[0], 10);
    const minutes = Number.parseInt(parts[1], 10);

    return new Date(
        date.getFullYear(),
        date.getMonth(),
        date.getDate(),
        Number.isFinite(hours) ? hours : 0,
        Number.isFinite(minutes) ? minutes : 0,
        0,
        0
    );
};

const fallbackPeriod = (now, windows = {}) => {
    const morningBetweenStart = parseLocalTime(now, windows.morningBetweenStart || '06:00');
    const dayStart = parseLocalTime(now, windows.dayStart || '07:00');
    const eveningBetweenStart = parseLocalTime(now, windows.eveningBetweenStart || '18:00');
    const nightStart = parseLocalTime(now, windows.nightStart || '19:00');

    if (now >= morningBetweenStart && now < dayStart) {
        return 'between';
    }

    if (now >= dayStart && now < eveningBetweenStart) {
        return 'day';
    }

    if (now >= eveningBetweenStart && now < nightStart) {
        return 'between';
    }

    return 'night';
};

const computePeriod = (now, config) => {
    const solar = config.solar || {};
    const latitude = Number.parseFloat(solar.latitude);
    const longitude = Number.parseFloat(solar.longitude);
    const twilightMinutes = Number.parseInt(
        solar.twilightMinutes ?? solar.twilightminutes ?? 60,
        10
    );

    const sunrise = solarEvent(now, latitude, longitude, true);
    const sunset = solarEvent(now, latitude, longitude, false);

    if (!sunrise || !sunset) {
        return {
            period: fallbackPeriod(now, config.fallbackWindows),
            sunrise: null,
            sunset: null,
        };
    }

    const twilightMs = Math.max(0, Number.isFinite(twilightMinutes) ? twilightMinutes : 60) * 60000;
    const morningBetweenStart = new Date(sunrise.getTime() - twilightMs);
    const eveningBetweenEnd = new Date(sunset.getTime() + twilightMs);

    let period = 'night';

    if (now >= morningBetweenStart && now < sunrise) {
        period = 'between';
    } else if (now >= sunrise && now < sunset) {
        period = 'day';
    } else if (now >= sunset && now < eveningBetweenEnd) {
        period = 'between';
    }

    return {period, sunrise, sunset};
};

const cssUrl = url => {
    return `url("${String(url).replace(/\\/g, '\\\\').replace(/"/g, '\\"')}")`;
};

export const init = config => {
    const body = document.body;

    if (!body || !config) {
        return;
    }

    const selector = config.selector || config.targetSelector || '.login-layout-left';
    const target = document.querySelector(selector);

    if (!target) {
        return;
    }

    const images = config.images || {};
    const now = new Date();
    const result = computePeriod(now, config);
    const period = result.period;

    const image = images[period] || config.fallback || images.night || images.between || images.day || '';

    BODY_CLASSES.forEach(className => body.classList.remove(className));
    body.classList.add(`theme-uckk-login-background--${period}`);

    body.dataset.themeUckkLoginPeriod = period;
    body.dataset.themeUckkLoginNow = now.toString();

    if (result.sunrise) {
        body.dataset.themeUckkLoginSunrise = result.sunrise.toString();
    }

    if (result.sunset) {
        body.dataset.themeUckkLoginSunset = result.sunset.toString();
    }

    if (image !== '') {
        target.style.setProperty('background-image', cssUrl(image), 'important');
    }
};