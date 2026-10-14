import type {CourseSection, ItemIndexEntry, Question} from './types';

/**
 * The course sections a question sends the learner back to: one per outcome it
 * assesses, each anchored on the heading that teaches it (owner's request of
 * 2026-10-14). Duplicates are dropped, since two outcomes can share a section.
 *
 * Empty when the payload carries no map, so a caller always has the course page
 * itself to fall back on.
 */
export function sectionsFor(question: Question, item: ItemIndexEntry | undefined): CourseSection[] {
  const map = item?.outcome_sections ?? {};
  const seen = new Set<string>();
  const sections: CourseSection[] = [];
  for (const outcome of question.assesses_outcomes ?? []) {
    const section = map[outcome];
    if (section && !seen.has(section.url)) {
      seen.add(section.url);
      sections.push(section);
    }
  }
  return sections;
}
