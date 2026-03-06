module.exports = {
    extends: ["@commitlint/config-conventional"],
    rules: {
        "type-enum": [
            2,
            "always",
            ["feat", "fix", "docs", "test", "ci", "refactor", "perf", "chore", "revert", "release"],
        ],
    },
};
