module.exports = {
    testEnvironment: 'jsdom',
    setupFilesAfterEnv: ['<rootDir>/tests/js/setup.js'],
    moduleNameMapper: {
        '\\.(css|less|scss)$': '<rootDir>/tests/js/__mocks__/styleMock.js'
    }
}; 