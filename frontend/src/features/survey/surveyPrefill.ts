import type { Respondent, ResponseDraft } from './types';

/**
 * Recursively finds all question names in a SurveyJS schema.
 * This includes names of pages, panels, and elements.
 */
function getAllNames(schema: any, names: Set<string>): void {
  if (!schema || typeof schema !== 'object') return;

  if (typeof schema.name === 'string') {
    names.add(schema.name);
  }

  // Common SurveyJS containers
  const childrenKeys = ['pages', 'elements', 'templateElements', 'questions'];
  for (const key of childrenKeys) {
    if (Array.isArray(schema[key])) {
      for (const item of schema[key]) {
        getAllNames(item, names);
      }
    }
  }
}

/**
 * Determines the initial answer JSON for a new survey response.
 * Priority: Draft > Respondent Pre-fill > Empty
 *
 * Pre-fills "name" and "email" only if questions with those names exist in the schema.
 */
export function getInitialAnswerJson(
  questionsJson: any,
  respondent: Respondent | null,
  draft: ResponseDraft | null
): Record<string, any> | undefined {
  // 1. If draft exists, it takes top priority
  if (draft) {
    return draft.answer_json;
  }

  // 2. If no respondent data, nothing to pre-fill
  if (!respondent || !questionsJson) {
    return undefined;
  }

  // 3. Find if "name" or "email" questions exist
  const existingNames = new Set<string>();
  getAllNames(questionsJson, existingNames);

  const initialData: Record<string, any> = {};

  if (respondent.name && existingNames.has('name')) {
    initialData['name'] = respondent.name;
  }

  if (respondent.email && existingNames.has('email')) {
    initialData['email'] = respondent.email;
  }

  return Object.keys(initialData).length > 0 ? initialData : undefined;
}
