// DigitalPersona WebSDK helper used by scan pages.
// Provides:
//   - Fingerprint.verify(userType): captures one sample and posts to php/fingerprint/api/verify.php
//   - Fingerprint.enroll(userId, userType): captures one sample and posts to php/fingerprint/api/enroll.php
//
// Notes:
// - This file intentionally "patches" the global Fingerprint object used across the project.
// - Scan pages previously referenced Fingerprint.verify(...) but the implementation file was missing.

(function () {
  const DEFAULT_VERIFY_ENDPOINT = "../php/fingerprint/api/verify.php";
  const DEFAULT_ENROLL_ENDPOINT = "../php/fingerprint/api/enroll.php";

  function loadScriptOnce(src) {
    return new Promise((resolve, reject) => {
      const existing = Array.from(document.getElementsByTagName("script")).find(
        (s) => s.src && s.src.includes(src)
      );
      if (existing) return resolve();

      const s = document.createElement("script");
      s.src = src;
      s.async = true;
      s.onload = () => resolve();
      s.onerror = () => reject(new Error("Failed to load script: " + src));
      document.head.appendChild(s);
    });
  }

  async function ensureDigitalPersonaLoaded() {
    // The web channel client is provided by websdk.client.bundle.min.js (WebSdk.WebChannelClient).
    if (typeof window.WebSdk === "undefined") {
      await loadScriptOnce("../js/websdk.client.bundle.min.js");
    }

    // Fingerprint.WebApi is provided by fingerprint.sdk.min.js.
    if (typeof window.Fingerprint === "undefined" || typeof window.Fingerprint.WebApi === "undefined") {
      await loadScriptOnce("../js/fingerprint.sdk.min.js");
    }
  }

  function pickFirstDevice(devices) {
    if (!Array.isArray(devices) || devices.length === 0) return "";
    return devices[0];
  }

  async function captureOneSample() {
    await ensureDigitalPersonaLoaded();

    const sdk = new window.Fingerprint.WebApi();
    const devices = await sdk.enumerateDevices();
    const deviceId = pickFirstDevice(devices);

    if (!deviceId) {
      throw new Error("No fingerprint reader found. Please connect the reader and try again.");
    }

    return await new Promise((resolve, reject) => {
      let done = false;

      const cleanup = () => {
        sdk.onSamplesAcquired = null;
        sdk.onCommunicationFailed = null;
        sdk.onErrorOccurred = null;
      };

      sdk.onCommunicationFailed = function () {
        if (done) return;
        done = true;
        cleanup();
        reject(new Error("Communication failed with the fingerprint service."));
      };

      sdk.onErrorOccurred = function (e) {
        if (done) return;
        done = true;
        cleanup();
        reject(new Error("Fingerprint device error."));
      };

      sdk.onSamplesAcquired = function (s) {
        if (done) return;
        done = true;
        try {
          const samples = JSON.parse(s.samples);
          const sampleData = samples && samples[0] && samples[0].Data ? samples[0].Data : "";
          cleanup();
          sdk.stopAcquisition(deviceId).catch(() => {});

          if (!sampleData) {
            reject(new Error("No fingerprint sample captured. Please try again."));
            return;
          }
          resolve(sampleData);
        } catch (err) {
          cleanup();
          sdk.stopAcquisition(deviceId).catch(() => {});
          reject(err);
        }
      };

      sdk
        .startAcquisition(window.Fingerprint.SampleFormat.Intermediate, deviceId)
        .catch((err) => {
          if (done) return;
          done = true;
          cleanup();
          reject(err);
        });
    });
  }

  async function postForm(url, dataObj) {
    const body = new URLSearchParams();
    Object.keys(dataObj).forEach((k) => body.append(k, String(dataObj[k])));

    const res = await fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
      body,
      credentials: "same-origin",
    });

    const text = await res.text();
    let json;
    try {
      json = JSON.parse(text);
    } catch {
      throw new Error("Server returned invalid JSON: " + text.slice(0, 200));
    }
    return json;
  }

  // Ensure global Fingerprint exists for callers.
  window.Fingerprint = window.Fingerprint || {};

  // Verification used by scan pages
  window.Fingerprint.verify = async function (userType) {
    const template = await captureOneSample();
    return await postForm(DEFAULT_VERIFY_ENDPOINT, {
      user_type: userType,
      template,
    });
  };

  // Optional: enrollment helper (not used by admin pages)
  window.Fingerprint.enroll = async function (userId, userType) {
    const template = await captureOneSample();
    return await postForm(DEFAULT_ENROLL_ENDPOINT, {
      user_id: userId,
      user_type: userType,
      template,
    });
  };
})();

