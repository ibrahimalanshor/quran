import { defineCollection } from "astro:content";
import { file } from "astro/loaders";

function verseParser(content: string) {
  console.log(content.split('\n'))
  return []
}

const verses = defineCollection({
  loader: file('src/data/verses.txt', { parser: verseParser })
})

export const collections = { verses }
