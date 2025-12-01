<?php

require_once __DIR__ . '/../bootstrap.php';

class ATSService
{
    // Analyze resume parsed JSON against job parsed JSON/text
    public static function analyze($resumeParsed, $jobParsed, $resumeText = '')
    {
        // normalize arrays
        $resumeParsed = $resumeParsed ?: [];
        $jobParsed = $jobParsed ?: [];

        $jd_skills = self::extractSkillsFromParsed($jobParsed);
        $resume_skills = self::extractSkillsFromParsed($resumeParsed);

        $totalKeywords = max(1, count($jd_skills));
        $matched = array_values(array_intersect($jd_skills, $resume_skills));
        $missing = array_values(array_diff($jd_skills, $resume_skills));

        // keyword score
        $keywordScore = count($matched) / $totalKeywords; // 0..1

        // section relevance: check where keywords appear (experience > skills > text)
        $sectionScore = 0.0;
        if (count($matched) > 0) {
            $sum = 0.0;
            foreach ($matched as $kw) {
                $found = 0.0;
                // experience check
                if (!empty($resumeParsed['experience']) && is_array($resumeParsed['experience'])) {
                    foreach ($resumeParsed['experience'] as $exp) {
                        $raw = strtolower($exp['raw'] ?? '');
                        if (strpos($raw, strtolower($kw)) !== false) {
                            $found = 1.0;
                            break;
                        }
                    }
                }
                // skills list
                if ($found === 0.0 && in_array($kw, $resume_skills)) {
                    $found = 0.6;
                }
                // anywhere in text
                if ($found === 0.0 && !empty($resumeText) && stripos($resumeText, $kw) !== false) {
                    $found = 0.4;
                }
                $sum += $found;
            }
            $sectionScore = $sum / count($matched);
        }

        // experience match: try to extract required years from jobParsed or job text
        $requiredYears = self::extractRequiredYears($jobParsed);
        $resumeYears = self::estimateResumeYears($resumeParsed, $resumeText);
        if ($requiredYears > 0) {
            $experienceScore = min(1.0, $resumeYears / $requiredYears);
        } else {
            $experienceScore = 1.0; // no requirement -> full points
        }

        // education/certs: presence-based
        $educationScore = 0.0;
        $eduKeywords = ['bachelor', 'master', 'b.sc', 'b.s.', 'm.sc', 'msc', 'phd', 'mba', 'degree', 'certification', 'certified'];
        $foundEdu = 0;
        foreach ($eduKeywords as $ek) {
            if ((!empty($resumeParsed['education']) && stripos(json_encode($resumeParsed['education']), $ek) !== false) || (!empty($resumeText) && stripos($resumeText, $ek) !== false)) {
                $foundEdu = 1;
                break;
            }
        }
        $educationScore = $foundEdu ? 1.0 : 0.0;

        // formatting/readability: penalize if parsing failed or too short
        $formatScore = 1.0;
        if (empty($resumeParsed) || empty($resumeText) || strlen($resumeText) < 200) {
            $formatScore = 0.4;
        }

        // weights
        $w_keyword = 0.40;
        $w_section = 0.20;
        $w_experience = 0.20;
        $w_education = 0.10;
        $w_format = 0.10;

        $score = ($w_keyword * $keywordScore) + ($w_section * $sectionScore) + ($w_experience * $experienceScore) + ($w_education * $educationScore) + ($w_format * $formatScore);
        $scorePercent = round($score * 100, 2);

        // suggestions: for each missing skill, provide a sample bullet
        $suggestions = [];
        foreach ($missing as $m) {
            $suggestions[] = [
                'type' => 'skill_gap',
                'skill' => $m,
                'suggestion' => "Consider adding a bullet highlighting experience with {$m}, e.g. 'Worked with {$m} to ...'"
            ];
        }

        // matched keywords list
        $matched_keywords = $matched;

        return [
            'ats_score' => $scorePercent,
            'matched_keywords' => $matched_keywords,
            'missing_skills' => $missing,
            'suggestions' => $suggestions,
            'details' => [
                'keywordScore' => round($keywordScore * 100, 2),
                'sectionScore' => round($sectionScore * 100, 2),
                'experienceScore' => round($experienceScore * 100, 2),
                'educationScore' => round($educationScore * 100, 2),
                'formatScore' => round($formatScore * 100, 2),
                'resumeYears' => $resumeYears,
                'requiredYears' => $requiredYears
            ]
        ];
    }

    private static function extractSkillsFromParsed($parsed)
    {
        $skills = [];
        if (!empty($parsed['skills']) && is_array($parsed['skills'])) {
            foreach ($parsed['skills'] as $s) {
                $skills[] = strtolower(trim($s));
            }
        }
        // fallback: look for keywords in jd_text if provided
        if (empty($skills) && !empty($parsed['jd_text'])) {
            $parts = preg_split('/[^a-zA-Z0-9\+\#\-]+/', strtolower($parsed['jd_text']));
            foreach ($parts as $p) {
                if (strlen($p) > 2) $skills[] = $p;
            }
        }
        // unique
        $skills = array_values(array_unique($skills));
        return $skills;
    }

    private static function extractRequiredYears($jobParsed)
    {
        // try to extract patterns like '3+ years' from jd_text
        $text = '';
        if (!empty($jobParsed['jd_text'])) $text = $jobParsed['jd_text'];
        if (empty($text) && !empty($jobParsed['raw'])) $text = $jobParsed['raw'];
        if ($text) {
            if (preg_match('/(\d+)\+?\s*years?/i', $text, $m)) {
                return intval($m[1]);
            }
        }
        return 0;
    }

    private static function estimateResumeYears($resumeParsed, $resumeText)
    {
        // Try to estimate experience by scanning for date ranges like 2018-2021
        $years = 0;
        if (!empty($resumeParsed['experience'])) {
            foreach ($resumeParsed['experience'] as $exp) {
                $raw = $exp['raw'] ?? '';
                if (preg_match('/(\d{4})\s*[\-–]\s*(\d{4}|present)/i', $raw, $m)) {
                    $start = intval($m[1]);
                    $end = (isset($m[2]) && preg_match('/\d{4}/', $m[2])) ? intval($m[2]) : intval(date('Y'));
                    $years += max(0, $end - $start);
                } elseif (preg_match('/(\d+)\s+years?/i', $raw, $m2)) {
                    $years += intval($m2[1]);
                }
            }
        }
        // fallback: try to find 'X years' in raw text
        if ($years === 0 && !empty($resumeText)) {
            if (preg_match('/(\d+)\s+years?/i', $resumeText, $m3)) {
                $years = intval($m3[1]);
            }
        }
        return $years;
    }
}
