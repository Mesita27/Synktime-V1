/**
 * SynkTime Python Service client utilities.
 *
 * Provides a single source of truth for the biometric microservice endpoints.
 */
(function (global) {
    const DEFAULT_BASE_URL = 'http://127.0.0.1:8000';
    const config = (global.SYNKTIME && global.SYNKTIME.pythonService) || {};

    const preferredBaseUrl = config.forcedIp || config.baseUrl || inferBaseUrl(config) || DEFAULT_BASE_URL;
    const normalizedBaseUrl = normalizeBaseUrl(preferredBaseUrl);
    const baseUrl = enforceHttpsIfNeeded(normalizedBaseUrl, config);
    const internalBaseUrl = normalizeOptionalBaseUrl(config.internalBaseUrl) || baseUrl;
    const healthPath = normalizePath(config.healthPath || null);
    const timeout = normalizeTimeout(config.timeout);
    const healthUrl = normalizeHealthUrl(config.healthUrl, baseUrl, healthPath);
    const proxyUrl = normalizeProxyUrl(config.proxyUrl);

    const api = {
        getBaseUrl() {
            return baseUrl;
        },
        getInternalBaseUrl() {
            return internalBaseUrl;
        },
        getHealthPath() {
            return healthPath;
        },
        getHealthUrl() {
            return healthUrl;
        },
        getTimeout() {
            return timeout;
        },
        getProxyUrl() {
            return proxyUrl;
        },
        buildUrl(path = '') {
            if (!path) {
                return baseUrl;
            }
            return joinPath(baseUrl, path);
        },
        fetch(path = '', options = {}) {
            return performFetch(path, options);
        }
    };

    Object.defineProperty(api, 'DEFAULT_BASE_URL', {
        value: DEFAULT_BASE_URL,
        enumerable: false
    });

    global.SynktimePythonService = api;

    function normalizeBaseUrl(url) {
        if (!url || typeof url !== 'string') {
            return DEFAULT_BASE_URL;
        }

        let normalized = url.trim();

        if (normalized === '') {
            return DEFAULT_BASE_URL;
        }

        if (!/^https?:\/\//i.test(normalized)) {
            normalized = 'http://' + normalized;
        }

        // Remove trailing slashes
        normalized = normalized.replace(/\/+$/, '');

        return normalized;
    }

    function normalizeOptionalBaseUrl(url) {
        if (!url || typeof url !== 'string') {
            return null;
        }

        const normalized = url.trim();
        if (normalized === '') {
            return null;
        }

        return normalizeBaseUrl(normalized);
    }

    function normalizePath(path) {
        if (!path || typeof path !== 'string') {
            return 'healthz';
        }

        const trimmed = path.trim();
        if (trimmed === '') {
            return 'healthz';
        }

        return trimmed.replace(/^\/+/, '').replace(/\/+$/, '') || 'healthz';
    }

    function normalizeTimeout(value) {
        const parsed = parseInt(value, 10);
        return Number.isFinite(parsed) && parsed > 0 ? parsed : 30;
    }

    function normalizeHealthUrl(explicitUrl, base, path) {
        if (explicitUrl && typeof explicitUrl === 'string' && explicitUrl.trim() !== '') {
            return explicitUrl.trim();
        }

        return joinPath(base, path);
    }

    function normalizeProxyUrl(value) {
        if (!value || typeof value !== 'string') {
            return '/api/biometric/python-proxy.php';
        }

        const trimmed = value.trim();
        if (trimmed === '') {
            return '/api/biometric/python-proxy.php';
        }

        if (/^https?:\/\//i.test(trimmed)) {
            return trimmed.replace(/\/+$/, '');
        }

        return trimmed.startsWith('/') ? trimmed : `/${trimmed}`;
    }

    function enforceHttpsIfNeeded(url, localConfig) {
        if (!/^http:\/\//i.test(url)) {
            return url;
        }

        if (isLoopback(url)) {
            return url;
        }

        if (shouldForceHttps(localConfig)) {
            return url.replace(/^http:\/\//i, 'https://');
        }

        return url;
    }

    function shouldForceHttps(localConfig) {
        if (localConfig && Object.prototype.hasOwnProperty.call(localConfig, 'forceHttps')) {
            return Boolean(localConfig.forceHttps);
        }

        try {
            return global.location && global.location.protocol === 'https:';
        } catch (error) {
            return false;
        }
    }

    function isLoopback(url) {
        try {
            const { hostname } = new URL(url);
            return hostname === '127.0.0.1' || hostname === 'localhost';
        } catch (error) {
            return false;
        }
    }

    function joinPath(base, path) {
        const root = base.replace(/\/+$/, '');
        const segment = String(path || '').replace(/^\/+/, '');
        return segment ? `${root}/${segment}` : root;
    }

    function inferBaseUrl(localConfig) {
        try {
            const { protocol, hostname } = global.location;
            if (!hostname) {
                return null;
            }

            const isSecure = protocol === 'https:';
            const port = derivePort(localConfig, hostname);
            const scheme = isSecure ? 'https:' : 'http:';

            return `${scheme}//${hostname}${port ? `:${port}` : ''}`;
        } catch (error) {
            return null;
        }
    }

    function derivePort(localConfig, hostname) {
        if (localConfig && localConfig.port) {
            return String(localConfig.port);
        }

        if (localConfig && typeof localConfig.baseUrl === 'string') {
            const match = localConfig.baseUrl.match(/:(\d+)(?:\/?$|\/)/);
            if (match) {
                return match[1];
            }
        }

        if (hostname === 'localhost' || hostname === '127.0.0.1') {
            return '8000';
        }

        return '8000';
    }

    function performFetch(path, options = {}) {
        const directUrl = buildAbsoluteUrl(path);
        const timeoutSeconds = extractTimeout(options, timeout);
        const forceProxy = Boolean(options && options.forceProxy);

        if (forceProxy || shouldProxyRequest(directUrl, null, options)) {
            return executeProxyFetch(directUrl, options, timeoutSeconds);
        }

        return executeFetch(directUrl, options, timeoutSeconds).catch((error) => {
            if (forceProxy) {
                return executeProxyFetch(directUrl, options, timeoutSeconds);
            }
            if (!shouldProxyRequest(directUrl, error, options)) {
                throw error;
            }
            return executeProxyFetch(directUrl, options, timeoutSeconds);
        });
    }

    function buildAbsoluteUrl(path) {
        if (!path) {
            return baseUrl;
        }

        if (/^https?:\/\//i.test(path)) {
            return path;
        }

        return joinPath(baseUrl, path);
    }

    function cloneFetchOptions(options) {
        const cloned = Object.assign({}, options || {});
    delete cloned.forceProxy;
        const originalHeaders = options && options.headers ? options.headers : undefined;

        if (originalHeaders instanceof Headers) {
            cloned.headers = new Headers(originalHeaders);
        } else if (Array.isArray(originalHeaders)) {
            cloned.headers = new Headers();
            originalHeaders.forEach((entry) => {
                if (!entry || entry.length < 2) {
                    return;
                }
                const [key, value] = entry;
                if (value !== undefined && value !== null) {
                    cloned.headers.append(key, value);
                }
            });
        } else if (originalHeaders && typeof originalHeaders === 'object') {
            cloned.headers = new Headers();
            Object.entries(originalHeaders).forEach(([key, value]) => {
                if (Array.isArray(value)) {
                    value.forEach((v) => cloned.headers.append(key, v));
                } else if (value !== undefined && value !== null) {
                    cloned.headers.append(key, value);
                }
            });
        } else {
            cloned.headers = new Headers();
        }

        if (cloned.method) {
            cloned.method = String(cloned.method).toUpperCase();
        }

        return cloned;
    }

    function extractTimeout(options, defaultTimeoutSeconds) {
        if (!options || options.timeoutSeconds === undefined || options.timeoutSeconds === null) {
            return defaultTimeoutSeconds;
        }

        const parsed = parseFloat(options.timeoutSeconds);
        return Number.isFinite(parsed) && parsed > 0 ? parsed : defaultTimeoutSeconds;
    }

    function shouldProxyRequest(url, error, options) {
        if (!proxyUrl) {
            return false;
        }

        if (options && options.disableProxyFallback) {
            return false;
        }

        const isHttpsPage = (() => {
            try {
                return global.location && global.location.protocol === 'https:';
            } catch (err) {
                return false;
            }
        })();

        const directIsHttp = /^http:\/\//i.test(url);
        if (isHttpsPage && directIsHttp) {
            return true;
        }

        const host = (() => {
            try {
                return new URL(url, global.location ? global.location.origin : undefined).hostname;
            } catch (err) {
                return '';
            }
        })();

        if (host && /^(synktime-python|localhost|127\.0\.0\.1)$/i.test(host)) {
            return true;
        }

        if (error && error.name === 'TypeError') {
            return true;
        }

        if (error && typeof error.message === 'string' && /failed to fetch/i.test(error.message)) {
            return true;
        }

        return false;
    }

    function executeFetch(url, options, timeoutSeconds) {
        const fetchOptions = cloneFetchOptions(options);
        delete fetchOptions.timeoutSeconds;
        return runWithTimeout(url, fetchOptions, timeoutSeconds);
    }

    function executeProxyFetch(url, options, timeoutSeconds) {
        if (!proxyUrl) {
            return Promise.reject(new Error('Proxy no disponible'));
        }

        let urlObj;
        try {
            urlObj = new URL(url, global.location ? global.location.origin : undefined);
        } catch (error) {
            return Promise.reject(error);
        }

        const fetchOptions = cloneFetchOptions(options);
        delete fetchOptions.timeoutSeconds;

        const pathWithQuery = `${urlObj.pathname.replace(/^\/+/, '')}${urlObj.search}`;
        const proxyHeaders = fetchOptions.headers instanceof Headers ? fetchOptions.headers : new Headers(fetchOptions.headers || {});
        const upstreamMethod = fetchOptions.method || 'GET';
        proxyHeaders.set('X-Synktime-Proxy-Path', pathWithQuery);
        proxyHeaders.set('X-Synktime-Proxy-Method', upstreamMethod);

        fetchOptions.headers = proxyHeaders;
        fetchOptions.mode = 'same-origin';
        fetchOptions.credentials = 'same-origin';
        fetchOptions.method = 'POST';

        if (fetchOptions.body === undefined || fetchOptions.body === null) {
            fetchOptions.body = JSON.stringify({
                method: upstreamMethod,
                target: pathWithQuery
            });
        }

        return runWithTimeout(proxyUrl, fetchOptions, timeoutSeconds);
    }

    function runWithTimeout(url, fetchOptions, timeoutSeconds) {
        const finalOptions = Object.assign({}, fetchOptions || {});

        if (finalOptions.signal || typeof AbortController === 'undefined' || !Number.isFinite(timeoutSeconds) || timeoutSeconds <= 0) {
            return global.fetch(url, finalOptions);
        }

        const controller = new AbortController();
        finalOptions.signal = controller.signal;

        const timeoutId = setTimeout(() => controller.abort(), timeoutSeconds * 1000);

        return global.fetch(url, finalOptions).finally(() => {
            clearTimeout(timeoutId);
        });
    }
})(window);
