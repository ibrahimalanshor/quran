import { defineCollection, z, reference } from "astro:content";
import { file } from "astro/loaders";
import { parse as parseCsv } from "csv-parse/sync";

function verseParser(content: string) {
  return parseCsv<{ id: string, ayah: string, arabic: string, latin: string, footnotes: string | null, translation: string, surah_id: string }>(content, { skip_empty_lines: true, columns: true })
    .map(verse => ({
      id: verse.id,
      chapter: verse.surah_id,
      text: verse.arabic,
      latin: verse.latin,
      footnotes: verse.footnotes === '' ? null : verse.footnotes,
      translation: verse.translation,
      verse: +verse.ayah
    }))
}

function tafsirParser(content: string) {
  return parseCsv<{ id: string, ayah: string, 'tafsir.wajiz': string, surah_id: string }>(content, { skip_empty_lines: true, columns: true })
    .map(verse => ({
      id: verse.id,
      chapter: verse.surah_id,
      text: verse['tafsir.wajiz'],
      verse: verse.ayah
    }))
}

function chapterParser(content: string) {
  return parseCsv<{ id: number, transliteration: string, num_ayah: number, page: number, arabic: string, translation: string, location: string }>(content, { columns: true })
    .map(chapter => ({
      id: chapter.id,
      slug: chapter.transliteration.toLowerCase().replace(/[^a-z-]/gi, ''),
      verses: +chapter.num_ayah,
      start: +chapter.page,
      name: chapter.arabic,
      latin: chapter.transliteration,
      translate: chapter.translation,
      type: chapter.location 
  }))
}

const verses = defineCollection({
  loader: file('src/data/verses.csv', { parser: verseParser }),
  schema: z.object({
    chapter: reference('chapters'),
    verse: z.number(),
    text: z.string(),
    latin: z.string(),
    footnotes: z.string().nullable(),
    translation: z.string()
  })
})

const tafsir = defineCollection({
  loader: file('src/data/tafsir.csv', { parser: tafsirParser }),
  schema: z.object({
    chapter: reference('chapters'),
    verse: reference('verses'),
    text: z.string(),
  })
})

const chapters = defineCollection({
  loader: file('src/data/chapters.csv', { parser: chapterParser }),
  schema: z.object({
    verses: z.number(),
    slug: z.string(),
    start: z.number(),
    name: z.string(),
    latin: z.string(),
    translate: z.string(),
    type: z.enum(['Makkiyah', 'Madaniyah'])
  })
})

export const collections = { verses, chapters, tafsir }
