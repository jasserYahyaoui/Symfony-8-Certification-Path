/*
 * Practice and Exam runtime.
 *
 * Master Plan §13: progress lives in this browser's localStorage. No account,
 * no network call, no secret. Export, import and reset are explicit.
 *
 * Master Plan §9.1 / §9.2: Practice reveals an answer only after submission;
 * Exam reveals nothing until the whole simulation is submitted.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'certpath.learner-state';
  var STORAGE_VERSION = 1;

  /* ---------------------------------------------------------------- storage */

  /**
   * Versioned local state. Every read runs the migration chain, so a learner
   * who returns after a schema change keeps their history (§13).
   */
  var Storage = {
    migrations: {
      // 1 -> 2 would live here, as `2: function (state) { ... }`.
    },

    empty: function () {
      return { schema_version: STORAGE_VERSION, attempts: [], sessions: [] };
    },

    read: function () {
      var raw;
      try {
        raw = window.localStorage.getItem(STORAGE_KEY);
      } catch (e) {
        return this.empty();
      }
      if (!raw) return this.empty();

      var state;
      try {
        state = JSON.parse(raw);
      } catch (e) {
        return this.empty();
      }
      return this.migrate(state);
    },

    migrate: function (state) {
      if (!state || typeof state !== 'object') return this.empty();
      var version = typeof state.schema_version === 'number' ? state.schema_version : 0;

      while (version < STORAGE_VERSION) {
        var migration = this.migrations[version + 1];
        if (!migration) return this.empty();
        state = migration(state);
        version += 1;
        state.schema_version = version;
      }

      if (!Array.isArray(state.attempts)) state.attempts = [];
      if (!Array.isArray(state.sessions)) state.sessions = [];
      return state;
    },

    write: function (state) {
      try {
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        return true;
      } catch (e) {
        return false;
      }
    },

    clear: function () {
      try {
        window.localStorage.removeItem(STORAGE_KEY);
        return true;
      } catch (e) {
        return false;
      }
    },

    recordAttempt: function (attempt) {
      var state = this.read();
      state.attempts.push(attempt);
      this.write(state);
    },

    recordSession: function (session) {
      var state = this.read();
      state.sessions.push(session);
      this.write(state);
    },

    /** Question ids the learner has answered incorrectly at least once. */
    weakQuestionIds: function () {
      var attempts = this.read().attempts;
      var weak = Object.create(null);
      attempts.forEach(function (a) {
        if (!a.correct) weak[a.question_id] = true;
      });
      return weak;
    }
  };

  /* ------------------------------------------------------------------ utils */

  function el(tag, attrs, children) {
    var node = document.createElement(tag);
    Object.keys(attrs || {}).forEach(function (key) {
      if (key === 'text') node.textContent = attrs[key];
      else if (key === 'html') node.innerHTML = attrs[key];
      else if (attrs[key] !== null && attrs[key] !== undefined) node.setAttribute(key, attrs[key]);
    });
    (children || []).forEach(function (child) { node.appendChild(child); });
    return node;
  }

  function shuffle(list) {
    var out = list.slice();
    for (var i = out.length - 1; i > 0; i--) {
      var j = Math.floor(Math.random() * (i + 1));
      var tmp = out[i]; out[i] = out[j]; out[j] = tmp;
    }
    return out;
  }

  function sameSet(a, b) {
    if (a.length !== b.length) return false;
    var sortedA = a.slice().sort();
    var sortedB = b.slice().sort();
    return sortedA.every(function (v, i) { return v === sortedB[i]; });
  }

  function loadPayload(url) {
    return fetch(url, { cache: 'no-store' }).then(function (response) {
      if (!response.ok) throw new Error('HTTP ' + response.status);
      return response.json();
    });
  }

  /* --------------------------------------------------------------- rendering */

  /**
   * Renders one question as a real fieldset with real labels, so the whole
   * thing is operable by keyboard and announced correctly (§13).
   */
  function renderQuestion(question, index, total, mode) {
    var form = el('form', { class: 'question', novalidate: 'novalidate' });
    var multiple = question.answer_mode === 'multiple';

    var fieldset = el('fieldset');
    fieldset.appendChild(el('legend', {
      text: 'Question ' + (index + 1) + ' sur ' + total
    }));

    fieldset.appendChild(el('p', { class: 'question-prompt', text: question.question }));

    if (question.negative_wording) {
      fieldset.appendChild(el('p', { class: 'note', text: 'Attention : formulation négative.' }));
    }

    fieldset.appendChild(el('p', {
      class: 'question-meta',
      text: multiple
        ? 'Réponses multiples — sélectionnez exactement ' + question.required_answer_count + ' réponses.'
        : 'Réponse unique.'
    }));

    var choices = question.shuffle_choices ? shuffle(question.choices) : question.choices;
    choices.forEach(function (choice) {
      var inputId = 'choice-' + choice.id;
      var input = el('input', {
        type: multiple ? 'checkbox' : 'radio',
        name: 'answer',
        id: inputId,
        value: choice.id
      });
      var label = el('label', { for: inputId, text: choice.text });
      fieldset.appendChild(el('div', { class: 'choice' }, [input, label]));
    });

    form.appendChild(fieldset);

    var actions = el('div', { class: 'button-row' });
    actions.appendChild(el('button', {
      type: 'submit',
      text: mode === 'exam' ? 'Enregistrer et continuer' : 'Valider ma réponse'
    }));
    form.appendChild(actions);

    return form;
  }

  function selectedIds(form) {
    return Array.prototype.slice
      .call(form.querySelectorAll('input[name="answer"]:checked'))
      .map(function (input) { return input.value; });
  }

  /* ----------------------------------------------------------- practice mode */

  function startPractice(root) {
    var status = document.getElementById('practice-status');

    loadPayload(root.getAttribute('data-payload')).then(function (payload) {
      var all = payload.questions || [];

      if (all.length === 0) {
        status.textContent =
          'Aucune question disponible pour le moment. La banque de questions sera '
          + 'remplie à partir du Lot 0.5 (Golden Slice).';
        return;
      }

      populateFilters(all);

      var state = { queue: [], index: 0 };

      function applyFilters() {
        var topic = value('filter-topic');
        var difficulty = value('filter-difficulty');
        var language = value('filter-language');
        var weakOnly = document.getElementById('filter-weak').checked;
        var weak = Storage.weakQuestionIds();

        state.queue = shuffle(all.filter(function (q) {
          if (topic && q.official_topic !== topic) return false;
          if (difficulty && q.difficulty !== difficulty) return false;
          if (language && q.language !== language) return false;
          if (weakOnly && !weak[q.id]) return false;
          return true;
        }));
        state.index = 0;
        render();
      }

      function render() {
        root.innerHTML = '';

        if (state.queue.length === 0) {
          root.appendChild(el('p', { role: 'status', text: 'Aucune question ne correspond à ces filtres.' }));
          return;
        }

        if (state.index >= state.queue.length) {
          root.appendChild(el('p', { role: 'status', text: 'Série terminée.' }));
          var again = el('button', { type: 'button', text: 'Recommencer' });
          again.addEventListener('click', applyFilters);
          root.appendChild(el('div', { class: 'button-row' }, [again]));
          return;
        }

        var question = state.queue[state.index];
        var form = renderQuestion(question, state.index, state.queue.length, 'practice');

        form.addEventListener('submit', function (event) {
          event.preventDefault();
          var chosen = selectedIds(form);
          if (chosen.length === 0) return;
          reveal(question, chosen, form);
        });

        root.appendChild(form);
      }

      function reveal(question, chosen, form) {
        var correctIds = question.choices
          .filter(function (c) { return c.correct; })
          .map(function (c) { return c.id; });
        var isCorrect = sameSet(chosen, correctIds);

        Storage.recordAttempt({
          question_id: question.id,
          question_version: question.version,
          official_item: question.official_item,
          correct: isCorrect,
          chosen: chosen,
          answered_at: new Date().toISOString()
        });

        Array.prototype.slice.call(form.querySelectorAll('input, button')).forEach(function (i) {
          i.disabled = true;
        });

        var result = isCorrect ? 'correct' : 'incorrect';
        var feedback = el('div', { class: 'feedback', 'data-result': result, role: 'status' });
        feedback.appendChild(el('p', {
          class: 'verdict',
          'data-result': result,
          text: isCorrect ? 'Réponse correcte' : 'Réponse incorrecte'
        }));
        feedback.appendChild(el('p', { text: question.explanation }));

        question.choices.forEach(function (choice) {
          if (!choice.correct && choice.explanation) {
            feedback.appendChild(el('p', {
              class: 'note',
              text: '« ' + choice.text +' » — ' + choice.explanation
            }));
          }
        });

        (question.official_sources || []).forEach(function (source) {
          if (!source.url) return;
          var link = el('a', { href: source.url, rel: 'noopener noreferrer', target: '_blank', text: source.url });
          feedback.appendChild(el('p', { class: 'note' }, [link]));
        });

        root.appendChild(feedback);

        var next = el('button', { type: 'button', text: 'Question suivante' });
        next.addEventListener('click', function () {
          state.index += 1;
          render();
          document.getElementById('main').focus();
        });
        root.appendChild(el('div', { class: 'button-row' }, [next]));
        next.focus();
      }

      function populateFilters(questions) {
        fill('filter-topic', questions.map(function (q) { return q.official_topic; }));
        fill('filter-difficulty', questions.map(function (q) { return q.difficulty; }));

        ['filter-topic', 'filter-difficulty', 'filter-language', 'filter-weak'].forEach(function (id) {
          var node = document.getElementById(id);
          if (node) node.addEventListener('change', applyFilters);
        });
      }

      applyFilters();
    }).catch(function (error) {
      status.textContent = 'Impossible de charger les questions : ' + error.message;
    });
  }

  function value(id) {
    var node = document.getElementById(id);
    return node ? node.value : '';
  }

  function fill(selectId, values) {
    var node = document.getElementById(selectId);
    if (!node) return;
    var unique = [];
    values.forEach(function (v) { if (v && unique.indexOf(v) === -1) unique.push(v); });
    unique.sort().forEach(function (v) {
      node.appendChild(el('option', { value: v, text: v }));
    });
  }

  /* --------------------------------------------------------------- exam mode */

  function startExam(root) {
    var startButton = document.getElementById('exam-start');
    var timerNode = document.getElementById('exam-timer');
    if (!startButton) return;

    startButton.addEventListener('click', function () {
      loadPayload(root.getAttribute('data-payload')).then(function (payload) {
        var pool = payload.questions || [];
        if (pool.length === 0) {
          root.innerHTML = '';
          root.appendChild(el('p', {
            role: 'status',
            text: 'Aucune question de simulation disponible. Le pool holdout sera constitué à partir du Lot 27.'
          }));
          return;
        }

        var count = Math.min(parseInt(value('exam-question-count'), 10) || 75, pool.length);
        var minutes = parseInt(value('exam-duration'), 10) || 90;
        run(shuffle(pool).slice(0, count), minutes * 60);
      }).catch(function (error) {
        root.innerHTML = '';
        root.appendChild(el('p', { role: 'status', text: 'Chargement impossible : ' + error.message }));
      });
    });

    function run(questions, totalSeconds) {
      var answers = Object.create(null);
      var index = 0;
      var startedAt = Date.now();
      var remaining = totalSeconds;
      var finished = false;

      timerNode.hidden = false;
      updateTimer();
      var ticker = window.setInterval(function () {
        remaining -= 1;
        updateTimer();
        /* §13: a timeout must never destroy work — it submits what exists. */
        if (remaining <= 0) finish(true);
      }, 1000);

      function updateTimer() {
        var safe = Math.max(remaining, 0);
        var mm = String(Math.floor(safe / 60)).padStart(2, '0');
        var ss = String(safe % 60).padStart(2, '0');
        timerNode.textContent = mm + ':' + ss;
        timerNode.setAttribute('data-state', safe <= 300 ? 'warning' : 'normal');
        /* Announce sparingly so a screen reader is not flooded every second. */
        timerNode.setAttribute('aria-live', safe === 300 || safe === 60 ? 'assertive' : 'off');
      }

      function render() {
        root.innerHTML = '';
        if (index >= questions.length) {
          var review = el('div');
          review.appendChild(el('p', {
            role: 'status',
            text: 'Toutes les questions ont été parcourues. Vous pouvez soumettre.'
          }));
          var submit = el('button', { type: 'button', text: 'Soumettre la simulation' });
          submit.addEventListener('click', function () { finish(false); });
          review.appendChild(el('div', { class: 'button-row' }, [submit]));
          root.appendChild(review);
          submit.focus();
          return;
        }

        var question = questions[index];
        var form = renderQuestion(question, index, questions.length, 'exam');

        (answers[question.id] || []).forEach(function (choiceId) {
          var input = form.querySelector('input[value="' + choiceId + '"]');
          if (input) input.checked = true;
        });

        form.addEventListener('submit', function (event) {
          event.preventDefault();
          answers[question.id] = selectedIds(form);
          index += 1;
          render();
        });

        root.appendChild(form);

        var early = el('button', { type: 'button', 'data-variant': 'secondary', text: 'Soumettre maintenant' });
        early.addEventListener('click', function () {
          answers[question.id] = selectedIds(form);
          finish(false);
        });
        root.appendChild(el('div', { class: 'button-row' }, [early]));
      }

      function finish(timedOut) {
        if (finished) return;
        finished = true;
        window.clearInterval(ticker);
        timerNode.hidden = true;

        var correct = 0;
        var unanswered = 0;
        var weakTopics = Object.create(null);

        questions.forEach(function (question) {
          var chosen = answers[question.id] || [];
          if (chosen.length === 0) unanswered += 1;

          var correctIds = question.choices
            .filter(function (c) { return c.correct; })
            .map(function (c) { return c.id; });
          var isCorrect = sameSet(chosen, correctIds);
          if (isCorrect) {
            correct += 1;
          } else {
            weakTopics[question.official_topic] = (weakTopics[question.official_topic] || 0) + 1;
          }

          Storage.recordAttempt({
            question_id: question.id,
            question_version: question.version,
            official_item: question.official_item,
            correct: isCorrect,
            chosen: chosen,
            answered_at: new Date().toISOString(),
            mode: 'exam'
          });
        });

        var elapsed = Math.round((Date.now() - startedAt) / 1000);
        Storage.recordSession({
          mode: 'exam',
          question_count: questions.length,
          correct: correct,
          unanswered: unanswered,
          elapsed_seconds: elapsed,
          timed_out: timedOut,
          finished_at: new Date().toISOString()
        });

        renderResults(correct, unanswered, elapsed, timedOut, weakTopics, questions.length);
      }

      function renderResults(correct, unanswered, elapsed, timedOut, weakTopics, total) {
        root.innerHTML = '';
        var section = el('div');
        section.appendChild(el('h3', { text: 'Résultat de la simulation' }));

        if (timedOut) {
          section.appendChild(el('p', {
            role: 'status',
            text: 'Le temps est écoulé. Vos réponses saisies ont été conservées et comptabilisées.'
          }));
        }

        var table = el('table');
        var tbody = el('tbody');
        [
          ['Score (1 point par question entièrement correcte)', correct + ' / ' + total],
          ['Sans réponse', String(unanswered)],
          ['Temps utilisé', Math.floor(elapsed / 60) + ' min ' + (elapsed % 60) + ' s']
        ].forEach(function (row) {
          tbody.appendChild(el('tr', {}, [el('th', { scope: 'row', text: row[0] }), el('td', { text: row[1] })]));
        });
        table.appendChild(tbody);
        section.appendChild(table);

        section.appendChild(el('p', {
          class: 'note',
          text: 'Politique de score interne (INTERNAL_TRAINING_FORMAT) : une question compte '
              + 'seulement si toutes ses bonnes réponses, et elles seules, sont sélectionnées. '
              + 'La politique officielle de notation n\'est pas publiée.'
        }));

        var topics = Object.keys(weakTopics).sort(function (a, b) { return weakTopics[b] - weakTopics[a]; });
        if (topics.length > 0) {
          section.appendChild(el('h3', { text: 'Sujets à revoir' }));
          var list = el('ul');
          topics.forEach(function (topic) {
            list.appendChild(el('li', { text: topic + ' — ' + weakTopics[topic] + ' erreur(s)' }));
          });
          section.appendChild(list);
        }

        root.appendChild(section);
        document.getElementById('main').focus();
      }

      render();
    }
  }

  /* ------------------------------------------------------- privacy controls */

  function bindPrivacyControls() {
    var status = document.getElementById('privacy-status');
    var exportButton = document.getElementById('export-progress');
    var resetButton = document.getElementById('reset-progress');

    if (exportButton) {
      exportButton.addEventListener('click', function () {
        var state = Storage.read();
        var text = JSON.stringify(state, null, 2);
        /*
         * The viewer sandbox blocks downloads a page starts itself, so the
         * export is offered as selectable text rather than a dead link.
         */
        var area = document.getElementById('export-output') || el('textarea', {
          id: 'export-output',
          rows: '10',
          'aria-label': 'Export JSON de votre progression',
          style: 'width:100%'
        });
        area.value = text;
        if (!area.parentNode) status.parentNode.insertBefore(area, status);
        status.textContent = 'Export généré (' + state.attempts.length + ' tentative(s)). Copiez ce JSON pour le conserver.';
        area.focus();
        area.select();
      });
    }

    if (resetButton) {
      resetButton.addEventListener('click', function () {
        if (!window.confirm('Effacer définitivement votre progression locale ?')) return;
        status.textContent = Storage.clear()
          ? 'Progression effacée.'
          : 'Effacement impossible : le stockage local est indisponible.';
      });
    }
  }

  /* -------------------------------------------------------------- bootstrap */

  document.addEventListener('DOMContentLoaded', function () {
    bindPrivacyControls();

    var practice = document.getElementById('practice-app');
    if (practice) startPractice(practice);

    var exam = document.getElementById('exam-app');
    if (exam) startExam(exam);
  });

  window.CertPath = { Storage: Storage, sameSet: sameSet };
})();
