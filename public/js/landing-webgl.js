/**
 * PowerMagz Gym — gravity scenes (custom WebGL, no dependencies)
 * ---------------------------------------------------------------------------
 * Two scenes share one tiny engine:
 *   hero — orbital energy rings and drifting dust around the athlete.
 *   core — a hand-built dumbbell that floats apart and assembles, with
 *          drag / arrow-key rotation, inertia and two orbit rings.
 *
 * Exposes window.PMGZScenes.create(canvas, options). Returns null when WebGL
 * is unavailable, so the caller keeps its CSS fallback.
 */
(function () {
    'use strict';

    function raf(fn) {
        return window.requestAnimationFrame(fn);
    }

    /* ------------------------------------------------------------- mat4 -- */
    function mat4() {
        var m = new Float32Array(16);
        m[0] = m[5] = m[10] = m[15] = 1;
        return m;
    }

    var M4 = {
        identity: function (o) {
            o[0] = 1; o[1] = 0; o[2] = 0; o[3] = 0;
            o[4] = 0; o[5] = 1; o[6] = 0; o[7] = 0;
            o[8] = 0; o[9] = 0; o[10] = 1; o[11] = 0;
            o[12] = 0; o[13] = 0; o[14] = 0; o[15] = 1;
            return o;
        },
        multiply: function (out, a, b) {
            var a00 = a[0], a01 = a[1], a02 = a[2], a03 = a[3],
                a10 = a[4], a11 = a[5], a12 = a[6], a13 = a[7],
                a20 = a[8], a21 = a[9], a22 = a[10], a23 = a[11],
                a30 = a[12], a31 = a[13], a32 = a[14], a33 = a[15],
                b0 = b[0], b1 = b[1], b2 = b[2], b3 = b[3];
            out[0] = b0 * a00 + b1 * a10 + b2 * a20 + b3 * a30;
            out[1] = b0 * a01 + b1 * a11 + b2 * a21 + b3 * a31;
            out[2] = b0 * a02 + b1 * a12 + b2 * a22 + b3 * a32;
            out[3] = b0 * a03 + b1 * a13 + b2 * a23 + b3 * a33;
            b0 = b[4]; b1 = b[5]; b2 = b[6]; b3 = b[7];
            out[4] = b0 * a00 + b1 * a10 + b2 * a20 + b3 * a30;
            out[5] = b0 * a01 + b1 * a11 + b2 * a21 + b3 * a31;
            out[6] = b0 * a02 + b1 * a12 + b2 * a22 + b3 * a32;
            out[7] = b0 * a03 + b1 * a13 + b2 * a23 + b3 * a33;
            b0 = b[8]; b1 = b[9]; b2 = b[10]; b3 = b[11];
            out[8] = b0 * a00 + b1 * a10 + b2 * a20 + b3 * a30;
            out[9] = b0 * a01 + b1 * a11 + b2 * a21 + b3 * a31;
            out[10] = b0 * a02 + b1 * a12 + b2 * a22 + b3 * a32;
            out[11] = b0 * a03 + b1 * a13 + b2 * a23 + b3 * a33;
            b0 = b[12]; b1 = b[13]; b2 = b[14]; b3 = b[15];
            out[12] = b0 * a00 + b1 * a10 + b2 * a20 + b3 * a30;
            out[13] = b0 * a01 + b1 * a11 + b2 * a21 + b3 * a31;
            out[14] = b0 * a02 + b1 * a12 + b2 * a22 + b3 * a32;
            out[15] = b0 * a03 + b1 * a13 + b2 * a23 + b3 * a33;
            return out;
        },
        perspective: function (o, fovy, aspect, near, far) {
            var f = 1 / Math.tan(fovy / 2);
            M4.identity(o);
            o[0] = f / aspect;
            o[5] = f;
            o[10] = (far + near) / (near - far);
            o[11] = -1;
            o[14] = (2 * far * near) / (near - far);
            o[15] = 0;
            return o;
        },
        lookAt: function (o, eye, center, up) {
            var z0 = eye[0] - center[0], z1 = eye[1] - center[1], z2 = eye[2] - center[2],
                len = 1 / Math.sqrt(z0 * z0 + z1 * z1 + z2 * z2);
            z0 *= len; z1 *= len; z2 *= len;
            var x0 = up[1] * z2 - up[2] * z1,
                x1 = up[2] * z0 - up[0] * z2,
                x2 = up[0] * z1 - up[1] * z0;
            len = Math.sqrt(x0 * x0 + x1 * x1 + x2 * x2);
            if (!len) { x0 = 0; x1 = 0; x2 = 0; } else { len = 1 / len; x0 *= len; x1 *= len; x2 *= len; }
            var y0 = z1 * x2 - z2 * x1,
                y1 = z2 * x0 - z0 * x2,
                y2 = z0 * x1 - z1 * x0;
            len = Math.sqrt(y0 * y0 + y1 * y1 + y2 * y2);
            if (!len) { y0 = 0; y1 = 0; y2 = 0; } else { len = 1 / len; y0 *= len; y1 *= len; y2 *= len; }
            o[0] = x0; o[1] = y0; o[2] = z0; o[3] = 0;
            o[4] = x1; o[5] = y1; o[6] = z1; o[7] = 0;
            o[8] = x2; o[9] = y2; o[10] = z2; o[11] = 0;
            o[12] = -(x0 * eye[0] + x1 * eye[1] + x2 * eye[2]);
            o[13] = -(y0 * eye[0] + y1 * eye[1] + y2 * eye[2]);
            o[14] = -(z0 * eye[0] + z1 * eye[1] + z2 * eye[2]);
            o[15] = 1;
            return o;
        },
        translate: function (o, x, y, z) {
            M4.identity(o);
            o[12] = x; o[13] = y; o[14] = z;
            return o;
        },
        scale: function (o, s) {
            M4.identity(o);
            o[0] = s; o[5] = s; o[10] = s;
            return o;
        },
        rotX: function (o, r) {
            var s = Math.sin(r), c = Math.cos(r);
            M4.identity(o);
            o[5] = c; o[6] = s; o[9] = -s; o[10] = c;
            return o;
        },
        rotY: function (o, r) {
            var s = Math.sin(r), c = Math.cos(r);
            M4.identity(o);
            o[0] = c; o[2] = -s; o[8] = s; o[10] = c;
            return o;
        },
        rotZ: function (o, r) {
            var s = Math.sin(r), c = Math.cos(r);
            M4.identity(o);
            o[0] = c; o[1] = s; o[4] = -s; o[5] = c;
            return o;
        }
    };

    /* ---------------------------------------------------------- geometry -- */
    function cylinder(radius, halfLength, segments) {
        var pos = [], nor = [], idx = [], i, a, s, c;
        for (i = 0; i <= segments; i++) {
            a = (i / segments) * Math.PI * 2;
            s = Math.sin(a); c = Math.cos(a);
            pos.push(radius * s, -halfLength, radius * c); nor.push(s, 0, c);
            pos.push(radius * s, halfLength, radius * c); nor.push(s, 0, c);
        }
        for (i = 0; i < segments; i++) {
            a = i * 2;
            idx.push(a, a + 2, a + 1, a + 1, a + 2, a + 3);
        }
        function cap(y, ny) {
            var center = pos.length / 3, start, j, aa, ss, cc;
            pos.push(0, y, 0); nor.push(0, ny, 0);
            start = pos.length / 3;
            for (j = 0; j <= segments; j++) {
                aa = (j / segments) * Math.PI * 2;
                ss = Math.sin(aa); cc = Math.cos(aa);
                pos.push(radius * ss, y, radius * cc); nor.push(0, ny, 0);
            }
            for (j = 0; j < segments; j++) {
                if (ny > 0) idx.push(center, start + j, start + j + 1);
                else idx.push(center, start + j + 1, start + j);
            }
        }
        cap(halfLength, 1);
        cap(-halfLength, -1);
        return { pos: pos, nor: nor, idx: idx };
    }

    function torus(major, minor, majorSegments, minorSegments) {
        var pos = [], nor = [], idx = [], i, j, u, v;
        for (i = 0; i <= majorSegments; i++) {
            u = (i / majorSegments) * Math.PI * 2;
            for (j = 0; j <= minorSegments; j++) {
                v = (j / minorSegments) * Math.PI * 2;
                var cu = Math.cos(u), su = Math.sin(u), cv = Math.cos(v), sv = Math.sin(v);
                pos.push((major + minor * cv) * cu, minor * sv, (major + minor * cv) * su);
                nor.push(cv * cu, sv, cv * su);
            }
        }
        var stride = minorSegments + 1;
        for (i = 0; i < majorSegments; i++) {
            for (j = 0; j < minorSegments; j++) {
                var a = i * stride + j;
                idx.push(a, a + stride, a + 1, a + 1, a + stride, a + stride + 1);
            }
        }
        return { pos: pos, nor: nor, idx: idx };
    }

    function upload(gl, geo) {
        var n = geo.pos.length / 3,
            data = new Float32Array(n * 6),
            i;
        for (i = 0; i < n; i++) {
            data[i * 6] = geo.pos[i * 3];
            data[i * 6 + 1] = geo.pos[i * 3 + 1];
            data[i * 6 + 2] = geo.pos[i * 3 + 2];
            data[i * 6 + 3] = geo.nor[i * 3];
            data[i * 6 + 4] = geo.nor[i * 3 + 1];
            data[i * 6 + 5] = geo.nor[i * 3 + 2];
        }
        var vbo = gl.createBuffer();
        gl.bindBuffer(gl.ARRAY_BUFFER, vbo);
        gl.bufferData(gl.ARRAY_BUFFER, data, gl.STATIC_DRAW);
        var ibo = gl.createBuffer();
        gl.bindBuffer(gl.ELEMENT_ARRAY_BUFFER, ibo);
        gl.bufferData(gl.ELEMENT_ARRAY_BUFFER, new Uint16Array(geo.idx), gl.STATIC_DRAW);
        return { vbo: vbo, ibo: ibo, count: geo.idx.length };
    }

    function createDust(gl, count, spreadX) {
        var positions = new Float32Array(count * 3),
            seeds = new Float32Array(count),
            i;
        for (i = 0; i < count; i++) {
            positions[i * 3] = (Math.random() * 2 - 1) * spreadX;
            positions[i * 3 + 1] = (Math.random() * 2 - 1) * 4;
            positions[i * 3 + 2] = (Math.random() * 2 - 1) * 3 - 1;
            seeds[i] = Math.random();
        }
        var pbo = gl.createBuffer();
        gl.bindBuffer(gl.ARRAY_BUFFER, pbo);
        gl.bufferData(gl.ARRAY_BUFFER, positions, gl.STATIC_DRAW);
        var sbo = gl.createBuffer();
        gl.bindBuffer(gl.ARRAY_BUFFER, sbo);
        gl.bufferData(gl.ARRAY_BUFFER, seeds, gl.STATIC_DRAW);
        return { pbo: pbo, sbo: sbo, count: count };
    }

    /* ----------------------------------------------------------- shaders -- */
    var METAL_VS = [
        'attribute vec3 aPos;',
        'attribute vec3 aNormal;',
        'uniform mat4 uProj, uView, uModel;',
        'varying vec3 vN, vP;',
        'void main(){',
        '  vec4 world = uModel * vec4(aPos, 1.0);',
        '  vP = world.xyz;',
        '  vN = mat3(uModel) * aNormal;',
        '  gl_Position = uProj * uView * world;',
        '}'
    ].join('\n');

    var METAL_FS = [
        'precision mediump float;',
        'varying vec3 vN, vP;',
        'uniform vec3 uEye, uAlbedo, uRim;',
        'void main(){',
        '  vec3 N = normalize(vN);',
        '  if (!gl_FrontFacing) N = -N;',
        '  vec3 V = normalize(uEye - vP);',
        '  vec3 key = normalize(vec3(-0.45, 0.85, 0.55));',
        '  vec3 fill = normalize(vec3(0.75, 0.1, 0.45));',
        '  float kd = max(dot(N, key), 0.0);',
        '  float fd = max(dot(N, fill), 0.0);',
        '  float fres = pow(1.0 - max(dot(N, V), 0.0), 3.0);',
        '  float spec = pow(max(dot(N, normalize(key + V)), 0.0), 42.0);',
        '  vec3 col = uAlbedo * (0.055 + 0.95 * pow(kd, 1.15));',
        '  col += vec3(0.12, 0.3, 0.3) * pow(fd, 2.2);',
        '  col += uRim * fres * 1.15;',
        '  col += vec3(0.92, 1.0, 0.98) * spec * 0.7;',
        '  col += vec3(0.05, 0.08, 0.08) * smoothstep(0.1, 0.95, N.y);',
        '  gl_FragColor = vec4(col, 1.0);',
        '}'
    ].join('\n');

    var ENERGY_VS = METAL_VS;

    var ENERGY_FS = [
        'precision mediump float;',
        'varying vec3 vN, vP;',
        'uniform vec3 uEye, uColor;',
        'uniform float uAlpha;',
        'void main(){',
        '  vec3 N = normalize(vN);',
        '  vec3 V = normalize(uEye - vP);',
        '  float f = pow(1.0 - abs(dot(N, V)), 2.0);',
        '  float a = uAlpha * (0.1 + 0.9 * f);',
        '  gl_FragColor = vec4(uColor * a, a);', // premultiplied — one additive blend
        '}'
    ].join('\n');

    var DUST_VS = [
        'attribute vec3 aPos;',
        'attribute float aSeed;',
        'uniform mat4 uProj, uView;',
        'uniform float uTime, uDpr, uSize;',
        'varying float vA;',
        'void main(){',
        '  vec3 p = aPos;',
        '  p.y = mod(p.y + uTime * (0.07 + aSeed * 0.14) + 4.0, 8.0) - 4.0;',
        '  p.x += sin(uTime * 0.4 + aSeed * 12.0) * 0.22;',
        '  vec4 mv = uView * vec4(p, 1.0);',
        '  gl_Position = uProj * mv;',
        '  gl_PointSize = (0.9 + aSeed * 2.2) * uDpr * uSize * (5.0 / max(1.0, -mv.z));',
        '  vA = 0.1 + aSeed * 0.45;',
        '}'
    ].join('\n');

    var DUST_FS = [
        'precision mediump float;',
        'varying float vA;',
        'uniform vec3 uColor;',
        'void main(){',
        '  float d = length(gl_PointCoord - 0.5);',
        '  float a = smoothstep(0.5, 0.05, d) * vA;',
        '  gl_FragColor = vec4(uColor * a, a);', // premultiplied — one additive blend
        '}'
    ].join('\n');

    function compile(gl, type, source) {
        var sh = gl.createShader(type);
        gl.shaderSource(sh, source);
        gl.compileShader(sh);
        if (!gl.getShaderParameter(sh, gl.COMPILE_STATUS)) {
            gl.deleteShader(sh);
            return null;
        }
        return sh;
    }

    function program(gl, vs, fs) {
        var v = compile(gl, gl.VERTEX_SHADER, vs);
        var f = compile(gl, gl.FRAGMENT_SHADER, fs);
        if (!v || !f) return null;
        var p = gl.createProgram();
        gl.attachShader(p, v);
        gl.attachShader(p, f);
        gl.linkProgram(p);
        gl.deleteShader(v);
        gl.deleteShader(f);
        if (!gl.getProgramParameter(p, gl.LINK_STATUS)) return null;
        return p;
    }

    /* ---------------------------------------------------------- renderer -- */
    function create(canvas, options) {
        var opts = options || {};
        var isCore = opts.kind === 'core';
        var pointer = opts.pointer || { x: 0, y: 0 };

        var gl = canvas.getContext('webgl', {
            alpha: true,
            antialias: true,
            premultipliedAlpha: true,
            powerPreference: 'low-power'
        });
        if (!gl) return null;

        var metal = program(gl, METAL_VS, METAL_FS);
        var energy = program(gl, ENERGY_VS, ENERGY_FS);
        var dust = program(gl, DUST_VS, DUST_FS);
        if (!metal || !energy || !dust) return null;

        var L = {
            aPos: gl.getAttribLocation(metal, 'aPos'),
            aNormal: gl.getAttribLocation(metal, 'aNormal'),
            uProj: gl.getUniformLocation(metal, 'uProj'),
            uView: gl.getUniformLocation(metal, 'uView'),
            uModel: gl.getUniformLocation(metal, 'uModel'),
            uEye: gl.getUniformLocation(metal, 'uEye'),
            uAlbedo: gl.getUniformLocation(metal, 'uAlbedo'),
            uRim: gl.getUniformLocation(metal, 'uRim')
        };
        var E = {
            aPos: gl.getAttribLocation(energy, 'aPos'),
            aNormal: gl.getAttribLocation(energy, 'aNormal'),
            uProj: gl.getUniformLocation(energy, 'uProj'),
            uView: gl.getUniformLocation(energy, 'uView'),
            uModel: gl.getUniformLocation(energy, 'uModel'),
            uEye: gl.getUniformLocation(energy, 'uEye'),
            uColor: gl.getUniformLocation(energy, 'uColor'),
            uAlpha: gl.getUniformLocation(energy, 'uAlpha')
        };
        var D = {
            aPos: gl.getAttribLocation(dust, 'aPos'),
            aSeed: gl.getAttribLocation(dust, 'aSeed'),
            uProj: gl.getUniformLocation(dust, 'uProj'),
            uView: gl.getUniformLocation(dust, 'uView'),
            uTime: gl.getUniformLocation(dust, 'uTime'),
            uDpr: gl.getUniformLocation(dust, 'uDpr'),
            uSize: gl.getUniformLocation(dust, 'uSize'),
            uColor: gl.getUniformLocation(dust, 'uColor')
        };

        var ringMesh = upload(gl, torus(1, 0.006, 72, 8));
        var dustSpread = isCore ? 4.5 : 7;
        var dustCount = (window.innerWidth < 760 ? 44 : 110);
        var dustMesh = createDust(gl, dustCount, dustSpread);

        var meshes = null;
        var parts = null;
        if (isCore) {
            meshes = {
                bar: upload(gl, cylinder(0.075, 2.05, 28)),
                plateBig: upload(gl, cylinder(0.62, 0.12, 56)),
                plateSmall: upload(gl, cylinder(0.44, 0.09, 48)),
                collar: upload(gl, cylinder(0.15, 0.07, 32)),
                cap: upload(gl, cylinder(0.1, 0.05, 28))
            };
            var teal = [0.05, 0.4, 0.4];
            var steel = [0.4, 0.44, 0.46];
            var dark = [0.24, 0.27, 0.28];
            var rim = [0.16, 0.72, 0.7];
            parts = [
                { mesh: 'bar', to: 0, from: 0, albedo: dark, rim: [0.14, 0.5, 0.48], phase: 0 },
                { mesh: 'plateBig', to: 1.3, from: 0.45, albedo: teal, rim: rim, phase: 1.2 },
                { mesh: 'plateSmall', to: 1.55, from: 0.95, albedo: steel, rim: rim, phase: 2.1 },
                { mesh: 'collar', to: 1.68, from: 1.4, albedo: steel, rim: rim, phase: 2.8 },
                { mesh: 'cap', to: 1.98, from: 1.75, albedo: dark, rim: rim, phase: 3.4 }
            ];
            parts = parts.concat(parts.slice(1).map(function (p) {
                var copy = {};
                for (var k in p) { if (Object.prototype.hasOwnProperty.call(p, k)) copy[k] = p[k]; }
                copy.mirror = true;
                copy.phase += 1.7;
                return copy;
            }));
        }

        var proj = mat4(), view = mat4(), eye = [0, 0.4, 6];
        var mBase = mat4(), mA = mat4(), mB = mat4(), mPart = mat4(), mC = mat4();
        var width = 0, height = 0, aspect = 1;
        var time = 0, last = 0, rafId = 0, running = false, visible = false, hidden = false;
        var assembly = 0, entered = false;
        var dragY = 0, dragX = 0, velY = 0, velX = 0, dragging = false;

        function resize() {
            var rect = canvas.getBoundingClientRect();
            var cssW = Math.max(1, Math.round(rect.width));
            var cssH = Math.max(1, Math.round(rect.height));
            var dpr = Math.min(window.devicePixelRatio || 1, isCore ? 1.75 : 1.6);
            var w = Math.round(cssW * dpr), h = Math.round(cssH * dpr);
            if (w !== canvas.width || h !== canvas.height) {
                canvas.width = w;
                canvas.height = h;
            }
            width = cssW; height = cssH;
            aspect = w / h;
            gl.viewport(0, 0, canvas.width, canvas.height);
        }

        function drawMetal(mesh, model, albedo, rimColor) {
            gl.useProgram(metal);
            gl.bindBuffer(gl.ARRAY_BUFFER, mesh.vbo);
            gl.enableVertexAttribArray(L.aPos);
            gl.vertexAttribPointer(L.aPos, 3, gl.FLOAT, false, 24, 0);
            gl.enableVertexAttribArray(L.aNormal);
            gl.vertexAttribPointer(L.aNormal, 3, gl.FLOAT, false, 24, 12);
            gl.uniformMatrix4fv(L.uProj, false, proj);
            gl.uniformMatrix4fv(L.uView, false, view);
            gl.uniformMatrix4fv(L.uModel, false, model);
            gl.uniform3fv(L.uEye, eye);
            gl.uniform3fv(L.uAlbedo, albedo);
            gl.uniform3fv(L.uRim, rimColor);
            gl.bindBuffer(gl.ELEMENT_ARRAY_BUFFER, mesh.ibo);
            gl.drawElements(gl.TRIANGLES, mesh.count, gl.UNSIGNED_SHORT, 0);
        }

        function drawEnergy(mesh, model, color, alpha) {
            gl.useProgram(energy);
            gl.bindBuffer(gl.ARRAY_BUFFER, mesh.vbo);
            gl.enableVertexAttribArray(E.aPos);
            gl.vertexAttribPointer(E.aPos, 3, gl.FLOAT, false, 24, 0);
            gl.enableVertexAttribArray(E.aNormal);
            gl.vertexAttribPointer(E.aNormal, 3, gl.FLOAT, false, 24, 12);
            gl.uniformMatrix4fv(E.uProj, false, proj);
            gl.uniformMatrix4fv(E.uView, false, view);
            gl.uniformMatrix4fv(E.uModel, false, model);
            gl.uniform3fv(E.uEye, eye);
            gl.uniform3fv(E.uColor, color);
            gl.uniform1f(E.uAlpha, alpha);
            gl.bindBuffer(gl.ELEMENT_ARRAY_BUFFER, mesh.ibo);
            gl.drawElements(gl.TRIANGLES, mesh.count, gl.UNSIGNED_SHORT, 0);
        }

        function drawDust(color, size) {
            gl.useProgram(dust);
            gl.bindBuffer(gl.ARRAY_BUFFER, dustMesh.pbo);
            gl.enableVertexAttribArray(D.aPos);
            gl.vertexAttribPointer(D.aPos, 3, gl.FLOAT, false, 12, 0);
            gl.bindBuffer(gl.ARRAY_BUFFER, dustMesh.sbo);
            gl.enableVertexAttribArray(D.aSeed);
            gl.vertexAttribPointer(D.aSeed, 1, gl.FLOAT, false, 4, 0);
            gl.uniformMatrix4fv(D.uProj, false, proj);
            gl.uniformMatrix4fv(D.uView, false, view);
            gl.uniform1f(D.uTime, time);
            gl.uniform1f(D.uDpr, Math.min(window.devicePixelRatio || 1, 2));
            gl.uniform1f(D.uSize, size);
            gl.uniform3fv(D.uColor, color);
            gl.drawArrays(gl.POINTS, 0, dustMesh.count);
        }

        function drawHero() {
            var narrow = aspect < 1.15;
            var scrollShift = Math.min(window.scrollY / Math.max(1, window.innerHeight), 1) * 0.35;
            var cx = (narrow ? 0.55 : 2.55) + pointer.x * 0.22;
            var cy = (narrow ? 0.3 : 0.1) - pointer.y * 0.14 + scrollShift;
            var base = narrow ? 1.25 : 1.85;
            var teal = [0.13, 0.9, 0.88];
            var pale = [0.75, 1, 1];
            for (var i = 0; i < 3; i++) {
                var s = base * (0.68 + i * 0.24);
                M4.translate(mA, cx + i * 0.1, cy, -i * 0.35);
                M4.rotY(mB, time * 0.12 + i * 1.7 + pointer.x * 0.3);
                M4.multiply(mC, mA, mB);
                M4.rotX(mA, 1.02 + i * 0.34 + pointer.y * 0.12);
                M4.multiply(mB, mC, mA);
                M4.rotZ(mA, i * 0.6);
                M4.multiply(mC, mB, mA);
                M4.scale(mA, s);
                M4.multiply(mPart, mC, mA);
                if (i === 0) drawEnergy(ringMesh, mPart, pale, 0.3);
                else drawEnergy(ringMesh, mPart, teal, i === 1 ? 0.34 : 0.16);
            }
            drawDust(teal, 1.15);
        }

        function drawCore(dt) {
            var target = entered ? 1 : 0;
            assembly += (target - assembly) * Math.min(1, dt * 2);
            if (target === 1 && assembly > 0.999) assembly = 1;

            if (!dragging) {
                dragY += velY;
                dragX += velX;
                velY *= 0.94;
                velX *= 0.94;
                if (Math.abs(velY) < 0.00002) velY = 0;
                if (Math.abs(velX) < 0.00002) velX = 0;
            }
            dragX = clampNum(dragX, -0.5, 0.5);

            var scale = clampNum(aspect / 1.15, 0.66, 1);
            var rotY = time * 0.34 + dragY + pointer.x * 0.22;
            var rotX = -0.16 + dragX + pointer.y * 0.1 + Math.sin(time * 0.5) * 0.03;
            var bob = Math.sin(time * 0.8) * 0.035;

            M4.rotY(mA, rotY);
            M4.rotX(mB, rotX);
            M4.multiply(mC, mA, mB);
            M4.translate(mA, 0, bob, 0);
            M4.multiply(mB, mC, mA);
            M4.scale(mA, scale);
            M4.multiply(mBase, mB, mA);

            gl.enable(gl.DEPTH_TEST);
            gl.depthMask(true);
            gl.disable(gl.BLEND);
            gl.disable(gl.CULL_FACE);
            for (var i = 0; i < parts.length; i++) {
                var p = parts[i];
                var spread = (1 - assembly) * (p.from - p.to);
                var float = Math.sin(time * 1.2 + p.phase) * 0.02 * assembly;
                var x = p.to + spread + float;
                if (p.mirror) x = -x;
                var y = Math.sin(time * 1.4 + p.phase) * 0.02 * assembly;
                M4.translate(mA, x, y, 0);
                M4.rotZ(mB, Math.PI / 2);
                M4.multiply(mPart, mA, mB);
                M4.multiply(mC, mBase, mPart);
                drawMetal(meshes[p.mesh], mC, p.albedo, p.rim);
            }

            gl.enable(gl.BLEND);
            gl.blendFuncSeparate(gl.ONE, gl.ONE, gl.ONE, gl.ONE);
            gl.depthMask(false);
            var teal = [0.14, 0.92, 0.9];
            for (i = 0; i < 2; i++) {
                var rs = (isCore ? 2.05 : 1) * (1 + i * 0.24) * scale;
                M4.rotY(mA, time * (0.22 + i * 0.1) + i * 0.8);
                M4.rotX(mB, 1.25 - i * 0.5 + Math.sin(time * 0.24 + i) * 0.08);
                M4.multiply(mC, mA, mB);
                M4.scale(mA, rs);
                M4.multiply(mPart, mC, mA);
                drawEnergy(ringMesh, mPart, teal, i === 0 ? 0.3 : 0.16);
            }
            drawDust(teal, 1.05);
        }

        function frame(now) {
            rafId = raf(frame);
            if (!last) last = now;
            var dt = Math.min((now - last) / 1000, 0.05);
            last = now;
            time += dt;

            var dist = isCore ? 6 : 8.6;
            eye[0] = pointer.x * (isCore ? 0.4 : 0.5);
            eye[1] = 0.35 + pointer.y * (isCore ? 0.18 : 0.25);
            eye[2] = dist;
            M4.perspective(proj, 0.72, aspect, 0.1, 40);
            M4.lookAt(view, eye, [0, 0, 0], [0, 1, 0]);

            gl.depthMask(true);
            gl.disable(gl.BLEND);
            gl.clearColor(0, 0, 0, 0);
            gl.clear(gl.COLOR_BUFFER_BIT | gl.DEPTH_BUFFER_BIT);

            if (isCore) {
                drawCore(dt);
            } else {
                gl.disable(gl.DEPTH_TEST);
                gl.enable(gl.BLEND);
                gl.blendFuncSeparate(gl.ONE, gl.ONE, gl.ONE, gl.ONE);
                gl.depthMask(false);
                drawHero();
            }
        }

        function start() {
            if (running || hidden) return;
            running = true;
            last = 0;
            resize();
            rafId = raf(frame);
        }

        function stop() {
            running = false;
            if (rafId) window.cancelAnimationFrame(rafId);
            rafId = 0;
        }

        function clampNum(v, min, max) {
            return v < min ? min : v > max ? max : v;
        }

        function setVisibility(next) {
            visible = next;
            if (visible) { entered = true; start(); } else { stop(); }
        }

        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (entries) {
                setVisibility(entries[0].isIntersecting);
            }, { threshold: 0.02 }).observe(canvas);
        } else {
            setVisibility(true);
        }

        document.addEventListener('visibilitychange', function () {
            hidden = document.hidden;
            if (hidden) stop();
            else if (visible) start();
        });

        if ('ResizeObserver' in window) {
            new ResizeObserver(resize).observe(canvas);
        } else {
            window.addEventListener('resize', resize);
        }

        return {
            start: start,
            stop: stop,
            rotate: function (dx, dy) {
                dragY += dx * 0.008;
                dragX += dy * 0.005;
                // Clamped so held arrow keys accelerate to a glide, not a spin.
                velY = clampNum(dx * 0.0009, -0.06, 0.06);
                velX = clampNum(dy * 0.0006, -0.05, 0.05);
            },
            release: function () {
                dragging = false;
            },
            hold: function () {
                dragging = true;
                velY = 0;
                velX = 0;
            },
            resize: resize
        };
    }

    window.PMGZScenes = { create: create };
})();
